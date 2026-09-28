<?php

declare(strict_types=1);

namespace LiteCrm\Enquiries;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use LiteCrm\Enums\EnquiryStatus;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Contact;
use LiteCrm\Models\Enquiry;
use LiteCrm\Models\Lookup;
use LiteCrm\Models\Organisation;
use LiteCrm\Models\Task;

/**
 * Turns an enquiry into an organisation and a contact without creating
 * duplicates: the user chooses between existing matches and new records, and
 * a new record is refused when an identical one already exists (organisation:
 * same name and city; contact: same e-mail), whether or not the user can see it.
 */
class EnquiryConverter
{
    public const EXISTING = 'existing';

    public const NEW = 'new';

    public const NONE = 'none';

    /**
     * Organisations with the same name (any city), for the user to choose from.
     *
     * @return Collection<int, Organisation>
     */
    public function organisationMatches(?string $name, mixed $user = null): Collection
    {
        if (blank($name)) {
            return new Collection;
        }

        $query = LiteCrm::model(Organisation::class)::query()->where(fn (Builder $query) => $this->whereNormalised($query, 'name', $name));

        if ($user !== null) {
            $query->scopes(['visibleTo' => [$user]]);
        }

        /** @var Collection<int, Organisation> $matches */
        $matches = $query->orderBy('name')->limit(20)->get();

        return $matches;
    }

    /**
     * Contacts with the same e-mail address.
     *
     * @return Collection<int, Contact>
     */
    public function contactMatches(?string $email, mixed $user = null): Collection
    {
        if (blank($email)) {
            return new Collection;
        }

        $query = LiteCrm::model(Contact::class)::query()->where(fn (Builder $query) => $this->whereNormalised($query, 'email', $email));

        if ($user !== null) {
            $query->scopes(['visibleTo' => [$user]]);
        }

        /** @var Collection<int, Contact> $matches */
        $matches = $query->limit(20)->get();

        return $matches;
    }

    /**
     * An existing organisation with this exact name and city (any visibility).
     */
    public function duplicateOrganisation(?string $name, ?string $city): ?Organisation
    {
        if (blank($name)) {
            return null;
        }

        /** @var Organisation|null $duplicate */
        $duplicate = LiteCrm::model(Organisation::class)::query()
            ->where(fn (Builder $query) => $this->whereNormalised($query, 'name', $name))
            ->where(function (Builder $query) use ($city): void {
                if (blank($city)) {
                    $query->whereNull('city')->orWhere('city', '');
                } else {
                    $this->whereNormalised($query, 'city', $city);
                }
            })
            ->first();

        return $duplicate;
    }

    /**
     * An existing contact with this e-mail address (any visibility).
     */
    public function duplicateContact(?string $email): ?Contact
    {
        if (blank($email)) {
            return null;
        }

        /** @var Contact|null $duplicate */
        $duplicate = LiteCrm::model(Contact::class)::query()
            ->where(fn (Builder $query) => $this->whereNormalised($query, 'email', $email))
            ->first();

        return $duplicate;
    }

    /**
     * @param  array{
     *     organisation_action?: string, organisation_id?: int|string|null,
     *     organisation?: array<string, mixed>,
     *     contact_action?: string, contact_id?: int|string|null,
     *     contact?: array<string, mixed>,
     *     task_title?: string|null, task_due_at?: string|null,
     * }  $choices
     *
     * @throws ValidationException when a choice would create a duplicate or is not allowed
     */
    public function convert(Enquiry $enquiry, array $choices, Model $user): Enquiry
    {
        if ($enquiry->status === EnquiryStatus::Converted) {
            throw ValidationException::withMessages(['enquiry' => __('lite-crm::enquiries.convert.already_converted')]);
        }

        return DB::transaction(function () use ($enquiry, $choices, $user): Enquiry {
            $organisation = $this->resolveOrganisation($choices, $user);
            $contact = $this->resolveContact($choices, $organisation, $user);

            $target = $contact ?? $organisation;

            if ($target !== null && LiteCrm::isModuleEnabled('activities')) {
                $target->activities()->create([
                    'type_id' => LiteCrm::model(Lookup::class)::query()->where('type', 'activity_type')->where('key', 'note')->value('id'),
                    'occurred_at' => $enquiry->created_at,
                    'summary' => __('lite-crm::enquiries.convert.activity_summary', [
                        'reference' => $enquiry->id,
                        'message' => Str::limit((string) $enquiry->message, 1000),
                    ]),
                ]);
            }

            if (filled($choices['task_title'] ?? null) && $target !== null && LiteCrm::isModuleEnabled('tasks')) {
                /** @var Task $task */
                $task = $target->tasks()->make([
                    'title' => (string) $choices['task_title'],
                    'due_at' => $choices['task_due_at'] ?? null,
                ]);
                $task->assignee_id = $enquiry->assignee_id ?? $user->getKey();
                $task->save();
            }

            $enquiry->forceFill([
                'organisation_id' => $organisation?->getKey(),
                'contact_id' => $contact?->getKey(),
                'status' => EnquiryStatus::Converted,
                'converted_at' => Date::now(),
                'converted_by' => $user->getKey(),
            ])->save();

            return $enquiry;
        });
    }

    /**
     * @param  array<string, mixed>  $choices
     */
    protected function resolveOrganisation(array $choices, Model $user): ?Organisation
    {
        $action = $choices['organisation_action'] ?? self::NONE;

        if ($action === self::EXISTING) {
            /** @var Organisation|null $organisation */
            $organisation = LiteCrm::model(Organisation::class)::query()
                ->scopes(['visibleTo' => [$user]])
                ->find($choices['organisation_id'] ?? null);

            if ($organisation === null) {
                throw ValidationException::withMessages(['organisation_id' => __('lite-crm::enquiries.convert.choose_organisation')]);
            }

            return $organisation;
        }

        if ($action !== self::NEW) {
            return null;
        }

        /** @var array<string, mixed> $data */
        $data = $choices['organisation'] ?? [];
        $name = trim((string) ($data['name'] ?? ''));
        $city = trim((string) ($data['city'] ?? ''));

        if ($name === '') {
            throw ValidationException::withMessages(['organisation.name' => __('lite-crm::enquiries.convert.organisation_name_required')]);
        }

        if ($this->duplicateOrganisation($name, $city) !== null) {
            throw ValidationException::withMessages(['organisation.name' => __('lite-crm::enquiries.convert.duplicate_organisation')]);
        }

        /** @var Organisation $organisation */
        $organisation = LiteCrm::model(Organisation::class)::query()->create([
            'name' => $name,
            'city' => $city !== '' ? $city : null,
            'country' => $data['country'] ?? null,
            'type_id' => $data['type_id'] ?? null,
            'source' => $data['source'] ?? null,
        ]);

        return $organisation;
    }

    /**
     * @param  array<string, mixed>  $choices
     */
    protected function resolveContact(array $choices, ?Organisation $organisation, Model $user): ?Contact
    {
        $action = $choices['contact_action'] ?? self::NONE;

        if ($action === self::EXISTING) {
            /** @var Contact|null $contact */
            $contact = LiteCrm::model(Contact::class)::query()
                ->scopes(['visibleTo' => [$user]])
                ->find($choices['contact_id'] ?? null);

            if ($contact === null) {
                throw ValidationException::withMessages(['contact_id' => __('lite-crm::enquiries.convert.choose_contact')]);
            }

            if ($organisation !== null && $contact->organisation_id === null) {
                $contact->update(['organisation_id' => $organisation->getKey()]);
            }

            return $contact;
        }

        if ($action !== self::NEW) {
            return null;
        }

        /** @var array<string, mixed> $data */
        $data = $choices['contact'] ?? [];
        $email = filled($data['email'] ?? null) ? Str::lower(trim((string) $data['email'])) : null;

        if (blank($data['last_name'] ?? null)) {
            throw ValidationException::withMessages(['contact.last_name' => __('lite-crm::enquiries.convert.last_name_required')]);
        }

        if ($this->duplicateContact($email) !== null) {
            throw ValidationException::withMessages(['contact.email' => __('lite-crm::enquiries.convert.duplicate_contact')]);
        }

        /** @var Contact $contact */
        $contact = LiteCrm::model(Contact::class)::query()->create([
            'organisation_id' => $organisation?->getKey(),
            'first_name' => $data['first_name'] ?? null,
            'last_name' => trim((string) $data['last_name']),
            'email' => $email,
            'phone' => $data['phone'] ?? null,
            'consent_basis' => $data['consent_basis'] ?? null,
            'consent_date' => $data['consent_date'] ?? null,
        ]);

        return $contact;
    }

    /**
     * Case- and whitespace-insensitive equality, in SQL that works on MariaDB/MySQL and SQLite.
     *
     * @param  Builder<covariant Model>  $query
     */
    protected function whereNormalised(Builder $query, string $column, string $value): void
    {
        $query->whereRaw('LOWER(TRIM('.$query->getQuery()->getGrammar()->wrap($column).')) = ?', [Str::lower(trim($value))]);
    }

    /**
     * Splits "Sara Khan" into ["Sara", "Khan"]; a single word becomes the last name.
     *
     * @return array{0: string|null, 1: string}
     */
    public static function splitName(?string $name): array
    {
        $parts = preg_split('/\s+/', trim((string) $name)) ?: [];
        $parts = array_values(array_filter($parts, fn (string $part): bool => $part !== ''));

        if (count($parts) <= 1) {
            return [null, $parts[0] ?? ''];
        }

        $last = (string) array_pop($parts);

        return [implode(' ', $parts), $last];
    }
}
