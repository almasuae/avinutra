<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Resources\Enquiries;

use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use LiteCrm\Enums\EnquiryStatus;
use LiteCrm\Filament\Resources\RecordResource;
use LiteCrm\Filament\Resources\Shared\ActivitiesRelationManager;
use LiteCrm\Filament\Resources\Shared\DocumentsRelationManager;
use LiteCrm\Filament\Resources\Shared\TasksRelationManager;
use LiteCrm\Filament\Support\Fields;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Enquiry;
use LiteCrm\Models\Lookup;

/**
 * The enquiry inbox.
 */
class EnquiryResource extends RecordResource
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'enquiries';

    public static function getModel(): string
    {
        return LiteCrm::model(Enquiry::class);
    }

    public static function getModelLabel(): string
    {
        return __('lite-crm::enquiries.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('lite-crm::enquiries.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('lite-crm::enquiries.navigation');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()->where('status', EnquiryStatus::New->value)->count();

        return $count > 0 ? (string) $count : null;
    }

    /**
     * For enquiries taken by phone or in person.
     */
    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            Fields::lookup('type_id', 'enquiry_type', __('lite-crm::enquiries.fields.type'))->required(),
            Fields::assignee(),
            TextInput::make('name')->label(__('lite-crm::enquiries.fields.name'))->maxLength(255),
            TextInput::make('company')->label(__('lite-crm::enquiries.fields.company'))->maxLength(255),
            TextInput::make('email')->label(__('lite-crm::enquiries.fields.email'))->email()->maxLength(255),
            TextInput::make('phone')->label(__('lite-crm::enquiries.fields.phone'))->tel()->maxLength(50),
            TextInput::make('city')->label(__('lite-crm::enquiries.fields.city'))->maxLength(255),
            TextInput::make('country')->label(__('lite-crm::enquiries.fields.country'))->maxLength(100),
            Textarea::make('message')->label(__('lite-crm::enquiries.fields.message'))->rows(5)->required()->columnSpanFull(),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make(__('lite-crm::enquiries.sections.enquiry'))->columns(3)->schema([
                TextEntry::make('status')->label(__('lite-crm::enquiries.fields.status'))->badge(),
                TextEntry::make('type.label')->label(__('lite-crm::enquiries.fields.type'))->badge()->placeholder('—'),
                TextEntry::make('created_at')->label(__('lite-crm::enquiries.fields.received'))->dateTime(),
                TextEntry::make('assignee.name')->label(__('lite-crm::enquiries.fields.assignee'))->placeholder(__('lite-crm::enquiries.unassigned')),
                TextEntry::make('first_response_at')->label(__('lite-crm::enquiries.fields.first_response_at'))->dateTime()->placeholder('—'),
                TextEntry::make('channel')->label(__('lite-crm::enquiries.fields.channel'))
                    ->formatStateUsing(fn (string $state): string => __("lite-crm::enquiries.channels.{$state}")),
                TextEntry::make('spam_reason')->label(__('lite-crm::enquiries.fields.spam_reason'))
                    ->formatStateUsing(fn (?string $state): string => $state === null ? '—' : __("lite-crm::enquiries.spam_reasons.{$state}"))
                    ->visible(fn (Enquiry $record): bool => $record->status === EnquiryStatus::Spam),
            ]),
            Section::make(__('lite-crm::enquiries.sections.sender'))->columns(3)->schema([
                TextEntry::make('name')->label(__('lite-crm::enquiries.fields.name'))->placeholder('—'),
                TextEntry::make('company')->label(__('lite-crm::enquiries.fields.company'))->placeholder('—'),
                TextEntry::make('email')->label(__('lite-crm::enquiries.fields.email'))->placeholder('—')->copyable(),
                TextEntry::make('phone')->label(__('lite-crm::enquiries.fields.phone'))->placeholder('—'),
                TextEntry::make('city')->label(__('lite-crm::enquiries.fields.city'))->placeholder('—'),
                TextEntry::make('country')->label(__('lite-crm::enquiries.fields.country'))->placeholder('—'),
                TextEntry::make('consent_at')->label(__('lite-crm::enquiries.fields.consent'))->dateTime()->placeholder(__('lite-crm::enquiries.no_consent')),
                TextEntry::make('source_url')->label(__('lite-crm::enquiries.fields.source_url'))->placeholder('—')->columnSpan(2),
            ]),
            Section::make(__('lite-crm::enquiries.fields.message'))->schema([
                TextEntry::make('message')->hiddenLabel()->placeholder('—'),
                KeyValueEntry::make('payload')->label(__('lite-crm::enquiries.fields.payload'))
                    ->visible(fn (Enquiry $record): bool => filled($record->payload)),
            ]),
            Section::make(__('lite-crm::enquiries.sections.conversion'))->columns(3)
                ->visible(fn (Enquiry $record): bool => $record->status === EnquiryStatus::Converted)
                ->schema([
                    TextEntry::make('organisation.name')->label(__('lite-crm::organisations.label'))->placeholder('—'),
                    TextEntry::make('contact.name')->label(__('lite-crm::contacts.label'))->placeholder('—'),
                    TextEntry::make('converted_at')->label(__('lite-crm::enquiries.fields.converted_at'))->dateTime(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label(__('lite-crm::enquiries.fields.received'))->dateTime()->sortable(),
                TextColumn::make('status')->label(__('lite-crm::enquiries.fields.status'))->badge(),
                TextColumn::make('type.label')->label(__('lite-crm::enquiries.fields.type'))->badge(),
                TextColumn::make('name')
                    ->label(__('lite-crm::enquiries.fields.from'))
                    ->formatStateUsing(fn (Enquiry $record): string => $record->displayName())
                    ->searchable(['name', 'company', 'email']),
                TextColumn::make('message')->label(__('lite-crm::enquiries.fields.message'))->limit(60)->wrap()->toggleable(),
                TextColumn::make('assignee.name')->label(__('lite-crm::enquiries.fields.assignee'))->placeholder(__('lite-crm::enquiries.unassigned')),
                TextColumn::make('country')->label(__('lite-crm::enquiries.fields.country'))->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')->label(__('lite-crm::enquiries.fields.status'))->options(EnquiryStatus::class),
                SelectFilter::make('type_id')->label(__('lite-crm::enquiries.fields.type'))->options(fn (): array => Lookup::options('enquiry_type')),
                SelectFilter::make('assignee_id')->label(__('lite-crm::enquiries.fields.assignee'))->options(fn (): array => LiteCrm::userOptions()),
                TrashedFilter::make(),
            ])
            ->recordActions([ViewAction::make()]);
    }

    public static function getRelations(): array
    {
        return array_values(array_filter([
            LiteCrm::isModuleEnabled('documents') ? DocumentsRelationManager::class : null,
            LiteCrm::isModuleEnabled('activities') ? ActivitiesRelationManager::class : null,
            LiteCrm::isModuleEnabled('tasks') ? TasksRelationManager::class : null,
        ]));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEnquiries::route('/'),
            'view' => Pages\ViewEnquiry::route('/{record}'),
        ];
    }

    /**
     * Assignee choices for the assign action.
     */
    public static function assigneeField(): Select
    {
        return Fields::assignee()->required();
    }
}
