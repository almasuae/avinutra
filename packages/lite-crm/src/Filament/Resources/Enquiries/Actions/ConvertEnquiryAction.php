<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Resources\Enquiries\Actions;

use Closure;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use LiteCrm\Enquiries\EnquiryConverter;
use LiteCrm\Enums\EnquiryStatus;
use LiteCrm\Filament\Resources\Opportunities\OpportunityResource;
use LiteCrm\Filament\Support\Fields;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Contact;
use LiteCrm\Models\Enquiry;
use LiteCrm\Models\Opportunity;
use LiteCrm\Models\Organisation;
use LiteCrm\Support\Visibility;

/**
 * Convert an enquiry into an organisation and a contact. Possible matches are
 * shown first; nothing is created until the user confirms, and a new record
 * that would duplicate an existing one is refused.
 */
class ConvertEnquiryAction
{
    public static function make(): Action
    {
        return Action::make('convert')
            ->label(__('lite-crm::enquiries.actions.convert'))
            ->icon(Heroicon::OutlinedArrowRightCircle)
            ->color('success')
            ->modalHeading(__('lite-crm::enquiries.convert.heading'))
            ->modalDescription(__('lite-crm::enquiries.convert.description'))
            ->modalSubmitActionLabel(__('lite-crm::enquiries.convert.submit'))
            ->visible(fn (Enquiry $record): bool => ! in_array($record->status, [EnquiryStatus::Converted, EnquiryStatus::Spam], true)
                && Gate::allows('update', $record)
                && (LiteCrm::isModuleEnabled('organisations') || LiteCrm::isModuleEnabled('contacts')))
            ->fillForm(fn (Enquiry $record): array => static::defaults($record))
            ->schema(fn (Enquiry $record): array => static::schema($record))
            ->action(function (Enquiry $record, array $data, Action $action): void {
                /** @var Model $user */
                $user = Filament::auth()->user();

                try {
                    app(EnquiryConverter::class)->convert($record, $data, $user);
                } catch (ValidationException $exception) {
                    Notification::make()
                        ->title(__('lite-crm::enquiries.convert.failed'))
                        ->body(Arr::first(Arr::flatten($exception->errors())))
                        ->danger()
                        ->send();

                    $action->halt();
                }

                Notification::make()->title(__('lite-crm::enquiries.convert.done'))->success()->send();
            });
    }

    /**
     * Pre-selects the likeliest choice: an existing match, else a new record.
     *
     * @return array<string, mixed>
     */
    public static function defaults(Enquiry $record): array
    {
        $converter = app(EnquiryConverter::class);
        $user = Filament::auth()->user();

        $organisations = $converter->organisationMatches($record->company, $user);
        $sameCity = $organisations->first(fn (Organisation $organisation): bool => mb_strtolower(trim((string) $organisation->city)) === mb_strtolower(trim((string) $record->city)));
        $contacts = $converter->contactMatches($record->email, $user);
        [$firstName, $lastName] = EnquiryConverter::splitName($record->name);

        return [
            'organisation_action' => match (true) {
                ! static::canCreateOrLink(Organisation::class) => EnquiryConverter::NONE,
                $organisations->isNotEmpty() => EnquiryConverter::EXISTING,
                filled($record->company) => EnquiryConverter::NEW,
                default => EnquiryConverter::NONE,
            },
            'organisation_id' => ($sameCity ?? $organisations->first())?->getKey(),
            'organisation' => [
                'name' => $record->company,
                'city' => $record->city,
                'country' => $record->country,
            ],
            'contact_action' => match (true) {
                ! static::canCreateOrLink(Contact::class) => EnquiryConverter::NONE,
                $contacts->isNotEmpty() => EnquiryConverter::EXISTING,
                filled($record->name) || filled($record->email) => EnquiryConverter::NEW,
                default => EnquiryConverter::NONE,
            },
            'contact_id' => $contacts->first()?->getKey(),
            'contact' => [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $record->email,
                'phone' => $record->phone,
            ],
        ];
    }

    /**
     * @return array<int, Section>
     */
    public static function schema(Enquiry $record): array
    {
        $converter = app(EnquiryConverter::class);
        $user = fn (): mixed => Filament::auth()->user();

        $organisationMatches = $converter->organisationMatches($record->company, $user())
            ->mapWithKeys(fn (Organisation $organisation): array => [$organisation->getKey() => static::organisationLabel($organisation)])
            ->all();
        $contactMatches = $converter->contactMatches($record->email, $user())
            ->mapWithKeys(fn (Contact $contact): array => [$contact->getKey() => $contact->name.($contact->email ? ' — '.$contact->email : '')])
            ->all();

        return [
            Section::make(__('lite-crm::organisations.label'))
                ->description($organisationMatches === []
                    ? __('lite-crm::enquiries.convert.no_organisation_match')
                    : __('lite-crm::enquiries.convert.organisation_matches', ['count' => count($organisationMatches)]))
                ->columns(2)
                ->visible(LiteCrm::isModuleEnabled('organisations'))
                ->schema([
                    Radio::make('organisation_action')
                        ->hiddenLabel()
                        ->options(static::actionOptions(Organisation::class, 'organisation'))
                        ->required()
                        ->live()
                        ->columnSpanFull(),
                    Select::make('organisation_id')
                        ->label(__('lite-crm::enquiries.convert.existing_organisation'))
                        ->options($organisationMatches)
                        ->searchable()
                        ->getSearchResultsUsing(fn (string $search): array => Visibility::apply(LiteCrm::model(Organisation::class)::query(), $user())
                            ->where('name', 'like', '%'.$search.'%')
                            ->orderBy('name')
                            ->limit(30)
                            ->get()
                            ->mapWithKeys(fn (Organisation $organisation): array => [$organisation->getKey() => static::organisationLabel($organisation)])
                            ->all())
                        ->getOptionLabelUsing(fn (mixed $value): ?string => ($organisation = LiteCrm::model(Organisation::class)::query()->find($value)) instanceof Organisation
                            ? static::organisationLabel($organisation) : null)
                        ->required(fn (Get $get): bool => $get('organisation_action') === EnquiryConverter::EXISTING)
                        ->visible(fn (Get $get): bool => $get('organisation_action') === EnquiryConverter::EXISTING)
                        ->columnSpanFull(),
                    TextInput::make('organisation.name')
                        ->label(__('lite-crm::organisations.fields.name'))
                        ->required(fn (Get $get): bool => $get('organisation_action') === EnquiryConverter::NEW)
                        ->maxLength(255)
                        ->rules([fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get, $converter): void {
                            if ($get('organisation_action') !== EnquiryConverter::NEW) {
                                return;
                            }

                            $duplicate = $converter->duplicateOrganisation(is_string($value) ? $value : null, $get('organisation.city'));

                            if ($duplicate !== null) {
                                $fail(__('lite-crm::enquiries.convert.duplicate_organisation_named', ['name' => static::organisationLabel($duplicate)]));
                            }
                        }])
                        ->visible(fn (Get $get): bool => $get('organisation_action') === EnquiryConverter::NEW),
                    TextInput::make('organisation.city')
                        ->label(__('lite-crm::organisations.fields.city'))
                        ->maxLength(255)
                        ->visible(fn (Get $get): bool => $get('organisation_action') === EnquiryConverter::NEW),
                    TextInput::make('organisation.country')
                        ->label(__('lite-crm::organisations.fields.country'))
                        ->maxLength(100)
                        ->visible(fn (Get $get): bool => $get('organisation_action') === EnquiryConverter::NEW),
                    Fields::lookup('organisation.type_id', 'organisation_type', __('lite-crm::organisations.fields.type'))
                        ->visible(fn (Get $get): bool => $get('organisation_action') === EnquiryConverter::NEW),
                ]),
            Section::make(__('lite-crm::contacts.label'))
                ->description($contactMatches === []
                    ? __('lite-crm::enquiries.convert.no_contact_match')
                    : __('lite-crm::enquiries.convert.contact_matches', ['count' => count($contactMatches)]))
                ->columns(2)
                ->visible(LiteCrm::isModuleEnabled('contacts'))
                ->schema([
                    Radio::make('contact_action')
                        ->hiddenLabel()
                        ->options(static::actionOptions(Contact::class, 'contact'))
                        ->required()
                        ->live()
                        ->columnSpanFull(),
                    Select::make('contact_id')
                        ->label(__('lite-crm::enquiries.convert.existing_contact'))
                        ->options($contactMatches)
                        ->searchable()
                        ->getSearchResultsUsing(fn (string $search): array => Visibility::apply(LiteCrm::model(Contact::class)::query(), $user())
                            ->where(fn ($query) => $query->where('last_name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%'))
                            ->limit(30)
                            ->get()
                            ->mapWithKeys(fn (Contact $contact): array => [$contact->getKey() => $contact->name.($contact->email ? ' — '.$contact->email : '')])
                            ->all())
                        ->getOptionLabelUsing(fn (mixed $value): ?string => ($contact = LiteCrm::model(Contact::class)::query()->find($value)) instanceof Contact
                            ? $contact->name : null)
                        ->required(fn (Get $get): bool => $get('contact_action') === EnquiryConverter::EXISTING)
                        ->visible(fn (Get $get): bool => $get('contact_action') === EnquiryConverter::EXISTING)
                        ->columnSpanFull(),
                    TextInput::make('contact.first_name')
                        ->label(__('lite-crm::contacts.fields.first_name'))
                        ->maxLength(255)
                        ->visible(fn (Get $get): bool => $get('contact_action') === EnquiryConverter::NEW),
                    TextInput::make('contact.last_name')
                        ->label(__('lite-crm::contacts.fields.last_name'))
                        ->required(fn (Get $get): bool => $get('contact_action') === EnquiryConverter::NEW)
                        ->maxLength(255)
                        ->visible(fn (Get $get): bool => $get('contact_action') === EnquiryConverter::NEW),
                    TextInput::make('contact.email')
                        ->label(__('lite-crm::contacts.fields.email'))
                        ->email()
                        ->maxLength(255)
                        ->rules([fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get, $converter): void {
                            if ($get('contact_action') !== EnquiryConverter::NEW) {
                                return;
                            }

                            $duplicate = $converter->duplicateContact(is_string($value) ? $value : null);

                            if ($duplicate !== null) {
                                $fail(__('lite-crm::enquiries.convert.duplicate_contact_named', ['name' => $duplicate->name]));
                            }
                        }])
                        ->visible(fn (Get $get): bool => $get('contact_action') === EnquiryConverter::NEW),
                    TextInput::make('contact.phone')
                        ->label(__('lite-crm::contacts.fields.phone'))
                        ->tel()
                        ->maxLength(50)
                        ->visible(fn (Get $get): bool => $get('contact_action') === EnquiryConverter::NEW),
                ]),
            Section::make(__('lite-crm::opportunities.label'))
                ->columns(2)
                ->visible(fn (): bool => LiteCrm::isModuleEnabled('opportunities') && Gate::allows('create', LiteCrm::model(Opportunity::class)))
                ->schema([
                    Toggle::make('opportunity.create')
                        ->label(__('lite-crm::enquiries.convert.create_opportunity'))
                        ->live()
                        ->columnSpanFull(),
                    TextInput::make('opportunity.name')
                        ->label(__('lite-crm::opportunities.fields.name'))
                        ->default($record->displayName())
                        ->maxLength(255)
                        ->visible(fn (Get $get): bool => (bool) $get('opportunity.create')),
                    Select::make('opportunity.pipeline_id')
                        ->label(__('lite-crm::opportunities.fields.pipeline'))
                        ->options(fn (): array => OpportunityResource::pipelineOptions())
                        ->default(fn (): mixed => array_key_first(OpportunityResource::pipelineOptions()))
                        ->required(fn (Get $get): bool => (bool) $get('opportunity.create'))
                        ->visible(fn (Get $get): bool => (bool) $get('opportunity.create')),
                ]),
            Section::make(__('lite-crm::enquiries.convert.follow_up'))
                ->columns(2)
                ->collapsed()
                ->visible(LiteCrm::isModuleEnabled('tasks'))
                ->schema([
                    TextInput::make('task_title')->label(__('lite-crm::tasks.fields.title'))->maxLength(255),
                    DateTimePicker::make('task_due_at')->label(__('lite-crm::tasks.fields.due_at'))->seconds(false),
                ]),
        ];
    }

    /**
     * @param  class-string<Model>  $model
     * @return array<string, string>
     */
    protected static function actionOptions(string $model, string $noun): array
    {
        $options = [EnquiryConverter::EXISTING => __("lite-crm::enquiries.convert.{$noun}_existing")];

        if (Gate::allows('create', LiteCrm::model($model))) {
            $options[EnquiryConverter::NEW] = __("lite-crm::enquiries.convert.{$noun}_new");
        }

        $options[EnquiryConverter::NONE] = __("lite-crm::enquiries.convert.{$noun}_none");

        return $options;
    }

    /**
     * @param  class-string<Model>  $model
     */
    protected static function canCreateOrLink(string $model): bool
    {
        return Gate::allows('viewAny', LiteCrm::model($model));
    }

    protected static function organisationLabel(Organisation $organisation): string
    {
        return $organisation->name.(filled($organisation->city) ? ' — '.$organisation->city : '');
    }
}
