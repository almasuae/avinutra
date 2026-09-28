<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Resources\Contacts;

use BackedEnum;
use DateTimeZone;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use LiteCrm\CustomFields\CustomFieldComponents;
use LiteCrm\Enums\ConsentBasis;
use LiteCrm\Enums\PreferredChannel;
use LiteCrm\Filament\Resources\RecordResource;
use LiteCrm\Filament\Resources\Shared\ActivitiesRelationManager;
use LiteCrm\Filament\Resources\Shared\DocumentsRelationManager;
use LiteCrm\Filament\Resources\Shared\TasksRelationManager;
use LiteCrm\Filament\Support\Fields;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Contact;
use LiteCrm\Support\Visibility;
use Livewire\Component;

class ContactResource extends RecordResource
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?int $navigationSort = 20;

    protected static ?string $slug = 'contacts';

    protected static ?string $recordTitleAttribute = 'last_name';

    public static function getModel(): string
    {
        return LiteCrm::model(Contact::class);
    }

    public static function getModelLabel(): string
    {
        return __('lite-crm::contacts.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('lite-crm::contacts.plural');
    }

    /**
     * @return list<string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['first_name', 'last_name', 'email'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record instanceof Contact ? $record->name : (string) $record->getKey();
    }

    public static function form(Schema $schema): Schema
    {
        $user = fn (): mixed => Filament::auth()->user();
        $zones = DateTimeZone::listIdentifiers();

        return $schema->columns(1)->components([
            Section::make(__('lite-crm::contacts.sections.details'))->columns(2)->schema([
                TextInput::make('first_name')->label(__('lite-crm::contacts.fields.first_name'))->maxLength(255),
                TextInput::make('last_name')->label(__('lite-crm::contacts.fields.last_name'))->required()->maxLength(255),
                Select::make('organisation_id')
                    ->label(__('lite-crm::contacts.fields.organisation'))
                    ->relationship('organisation', 'name', fn (Builder $query) => Visibility::apply($query, $user()))
                    ->searchable()
                    ->preload()
                    ->hidden(fn (Component $livewire): bool => $livewire instanceof RelationManager || ! LiteCrm::isModuleEnabled('organisations')),
                TextInput::make('job_title')->label(__('lite-crm::contacts.fields.job_title'))->maxLength(255),
                TextInput::make('department')->label(__('lite-crm::contacts.fields.department'))->maxLength(255),
                Fields::owner(),
                Fields::tags(),
            ]),
            Section::make(__('lite-crm::contacts.sections.reach'))->columns(2)->schema([
                TextInput::make('email')->label(__('lite-crm::contacts.fields.email'))->email()->maxLength(255),
                TextInput::make('phone')->label(__('lite-crm::contacts.fields.phone'))->tel()->maxLength(50),
                TextInput::make('whatsapp')->label(__('lite-crm::contacts.fields.whatsapp'))->tel()->maxLength(50),
                Select::make('preferred_channel')->label(__('lite-crm::contacts.fields.preferred_channel'))->options(PreferredChannel::class),
                TagsInput::make('languages')->label(__('lite-crm::contacts.fields.languages')),
                Select::make('time_zone')
                    ->label(__('lite-crm::contacts.fields.time_zone'))
                    ->options(array_combine($zones, $zones))
                    ->searchable(),
            ]),
            Section::make(__('lite-crm::contacts.sections.consent'))
                ->description(__('lite-crm::contacts.sections.consent_help'))
                ->columns(2)
                ->schema([
                    Select::make('consent_basis')->label(__('lite-crm::contacts.fields.consent_basis'))->options(ConsentBasis::class),
                    DatePicker::make('consent_date')->label(__('lite-crm::contacts.fields.consent_date')),
                ]),
            ...CustomFieldComponents::form('contact'),
            Section::make(__('lite-crm::contacts.fields.notes'))->collapsible()->schema([
                Textarea::make('notes')->hiddenLabel()->rows(4),
            ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make(__('lite-crm::contacts.sections.details'))->columns(3)->schema([
                TextEntry::make('organisation.name')->label(__('lite-crm::contacts.fields.organisation'))->placeholder('—'),
                TextEntry::make('job_title')->label(__('lite-crm::contacts.fields.job_title'))->placeholder('—'),
                TextEntry::make('department')->label(__('lite-crm::contacts.fields.department'))->placeholder('—'),
                TextEntry::make('owner.name')->label(__('lite-crm::common.fields.owner'))->placeholder('—'),
                TextEntry::make('tags.name')->label(__('lite-crm::common.fields.tags'))->badge()->placeholder('—'),
            ]),
            Section::make(__('lite-crm::contacts.sections.reach'))->columns(3)->schema([
                TextEntry::make('email')->label(__('lite-crm::contacts.fields.email'))->placeholder('—')->copyable(),
                TextEntry::make('phone')->label(__('lite-crm::contacts.fields.phone'))->placeholder('—'),
                TextEntry::make('whatsapp')->label(__('lite-crm::contacts.fields.whatsapp'))->placeholder('—'),
                TextEntry::make('preferred_channel')->label(__('lite-crm::contacts.fields.preferred_channel'))->placeholder('—'),
                TextEntry::make('languages')->label(__('lite-crm::contacts.fields.languages'))->badge()->placeholder('—'),
                TextEntry::make('time_zone')->label(__('lite-crm::contacts.fields.time_zone'))->placeholder('—'),
            ]),
            Section::make(__('lite-crm::contacts.sections.consent'))->columns(2)->schema([
                TextEntry::make('consent_basis')->label(__('lite-crm::contacts.fields.consent_basis'))->placeholder('—'),
                TextEntry::make('consent_date')->label(__('lite-crm::contacts.fields.consent_date'))->date()->placeholder('—'),
            ]),
            ...CustomFieldComponents::infolist('contact'),
            Section::make(__('lite-crm::contacts.fields.notes'))->collapsible()->schema([
                TextEntry::make('notes')->hiddenLabel()->placeholder('—'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('last_name')
                    ->label(__('lite-crm::contacts.fields.name'))
                    ->formatStateUsing(fn (Contact $record): string => $record->name)
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(),
                TextColumn::make('organisation.name')
                    ->label(__('lite-crm::contacts.fields.organisation'))
                    ->hidden(fn (Component $livewire): bool => $livewire instanceof RelationManager),
                TextColumn::make('job_title')->label(__('lite-crm::contacts.fields.job_title'))->toggleable(),
                TextColumn::make('email')->label(__('lite-crm::contacts.fields.email'))->searchable()->copyable(),
                TextColumn::make('phone')->label(__('lite-crm::contacts.fields.phone'))->toggleable(),
                TextColumn::make('owner.name')->label(__('lite-crm::common.fields.owner'))->toggleable(isToggledHiddenByDefault: true),
                ...CustomFieldComponents::tableColumns('contact'),
            ])
            ->defaultSort('last_name')
            ->filters([
                SelectFilter::make('owner_id')->label(__('lite-crm::common.fields.owner'))->options(fn (): array => LiteCrm::userOptions()),
                SelectFilter::make('preferred_channel')->label(__('lite-crm::contacts.fields.preferred_channel'))->options(PreferredChannel::class),
                SelectFilter::make('tags')->label(__('lite-crm::common.fields.tags'))->relationship('tags', 'name')->multiple(),
                ...CustomFieldComponents::tableFilters('contact'),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make()->url(fn (Contact $record): string => static::getUrl('view', ['record' => $record])),
                EditAction::make()->url(fn (Contact $record): string => static::getUrl('edit', ['record' => $record])),
                DeleteAction::make(),
                RestoreAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return array_values(array_filter([
            LiteCrm::isModuleEnabled('activities') ? ActivitiesRelationManager::class : null,
            LiteCrm::isModuleEnabled('tasks') ? TasksRelationManager::class : null,
            LiteCrm::isModuleEnabled('documents') ? DocumentsRelationManager::class : null,
        ]));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContacts::route('/'),
            'create' => Pages\CreateContact::route('/create'),
            'view' => Pages\ViewContact::route('/{record}'),
            'edit' => Pages\EditContact::route('/{record}/edit'),
        ];
    }
}
