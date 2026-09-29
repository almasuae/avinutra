<?php

declare(strict_types=1);

namespace LiteCrm\ImportExport;

use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Forms\Components\Checkbox;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use LiteCrm\Enquiries\EnquiryConverter;
use LiteCrm\ImportExport\Concerns\HandlesCrmColumns;
use LiteCrm\ImportExport\Concerns\ReportsCompletion;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Contact;
use LiteCrm\Models\Organisation;

/**
 * Contacts from CSV. "organisation" is matched by name (and "organisation_city"
 * when given). A row with an e-mail address that already exists updates that
 * contact when "Update existing records" is ticked; otherwise it is skipped.
 */
class ContactImporter extends Importer
{
    use HandlesCrmColumns;
    use ReportsCompletion;

    protected static ?string $model = Contact::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('first_name')->label(__('lite-crm::contacts.fields.first_name'))->rules(['nullable', 'max:255']),
            ImportColumn::make('last_name')->label(__('lite-crm::contacts.fields.last_name'))->requiredMapping()->rules(['required', 'max:255']),
            ImportColumn::make('email')->label(__('lite-crm::contacts.fields.email'))->rules(['nullable', 'email', 'max:255'])
                ->castStateUsing(fn (mixed $state): ?string => blank($state) ? null : Str::lower(trim((string) $state))),
            ImportColumn::make('organisation')
                ->label(__('lite-crm::contacts.fields.organisation'))
                ->rules(['nullable', 'max:255'])
                ->fillRecordUsing(function (Model $record, mixed $state, array $data): void {
                    if (blank($state)) {
                        return;
                    }

                    $organisation = app(EnquiryConverter::class)->organisationMatches((string) $state)
                        ->first(fn (Organisation $organisation): bool => blank($data['organisation_city'] ?? null)
                            || mb_strtolower(trim((string) $organisation->city)) === mb_strtolower(trim((string) $data['organisation_city'])));

                    if ($organisation === null) {
                        throw new RowImportFailedException(__('lite-crm::import.organisation_not_found', ['name' => $state]));
                    }

                    $record->setAttribute('organisation_id', $organisation->getKey());
                }),
            ImportColumn::make('organisation_city')->label(__('lite-crm::import.organisation_city'))->fillRecordUsing(fn () => null),
            ImportColumn::make('job_title')->label(__('lite-crm::contacts.fields.job_title'))->rules(['nullable', 'max:255']),
            ImportColumn::make('department')->label(__('lite-crm::contacts.fields.department'))->rules(['nullable', 'max:255']),
            ImportColumn::make('phone')->label(__('lite-crm::contacts.fields.phone'))->rules(['nullable', 'max:50']),
            ImportColumn::make('whatsapp')->label(__('lite-crm::contacts.fields.whatsapp'))->rules(['nullable', 'max:50']),
            ImportColumn::make('languages')
                ->label(__('lite-crm::contacts.fields.languages'))
                ->castStateUsing(fn (mixed $state): ?array => blank($state) ? null : array_values(array_filter(array_map('trim', explode(',', (string) $state))))),
            ImportColumn::make('time_zone')->label(__('lite-crm::contacts.fields.time_zone'))->rules(['nullable', 'timezone']),
            ImportColumn::make('notes')->label(__('lite-crm::contacts.fields.notes'))->rules(['nullable', 'max:10000']),
            ...static::customImportColumns('contact'),
        ];
    }

    public static function getOptionsFormComponents(): array
    {
        return [Checkbox::make('update_existing')->label(__('lite-crm::import.update_existing'))];
    }

    public function resolveRecord(): ?Contact
    {
        $existing = app(EnquiryConverter::class)->duplicateContact($this->data['email'] ?? null);

        if ($existing !== null) {
            if (! ($this->options['update_existing'] ?? false)) {
                throw new RowImportFailedException(__('lite-crm::import.duplicate_contact', ['email' => $existing->email]));
            }

            if (Gate::denies('update', $existing)) {
                throw new RowImportFailedException(__('lite-crm::import.not_allowed_to_update'));
            }

            return $existing;
        }

        /** @var Contact $contact */
        $contact = new (LiteCrm::model(Contact::class));

        return $contact;
    }
}
