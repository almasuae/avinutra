<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Resources\Organisations;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use LiteCrm\CustomFields\CustomFieldComponents;
use LiteCrm\Filament\Resources\RecordResource;
use LiteCrm\Filament\Resources\Shared\ActivitiesRelationManager;
use LiteCrm\Filament\Resources\Shared\DocumentsRelationManager;
use LiteCrm\Filament\Resources\Shared\TasksRelationManager;
use LiteCrm\Filament\Support\Fields;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Lookup;
use LiteCrm\Models\Organisation;

class OrganisationResource extends RecordResource
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'organisations';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModel(): string
    {
        return LiteCrm::model(Organisation::class);
    }

    public static function getModelLabel(): string
    {
        return __('lite-crm::organisations.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('lite-crm::organisations.plural');
    }

    /**
     * @return list<string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'city', 'email'];
    }

    public static function form(Schema $schema): Schema
    {
        $typeKey = fn (Get $get): ?string => Lookup::keyFor($get('type_id'));

        return $schema->columns(1)->components([
            Section::make(__('lite-crm::organisations.sections.details'))->columns(2)->schema([
                TextInput::make('name')->label(__('lite-crm::organisations.fields.name'))->required()->maxLength(255)->columnSpanFull(),
                Fields::lookup('type_id', 'organisation_type', __('lite-crm::organisations.fields.type'))->live(),
                Fields::lookup('status_id', 'organisation_status', __('lite-crm::organisations.fields.status')),
                Fields::lookup('territory_id', 'territory', __('lite-crm::organisations.fields.territory')),
                TextInput::make('source')->label(__('lite-crm::organisations.fields.source'))->maxLength(255),
                Fields::owner(),
                Fields::tags(),
            ]),
            Section::make(__('lite-crm::organisations.sections.contact'))->columns(2)->schema([
                TextInput::make('website')->label(__('lite-crm::organisations.fields.website'))->url()->maxLength(255),
                TextInput::make('email')->label(__('lite-crm::organisations.fields.email'))->email()->maxLength(255),
                TextInput::make('phone')->label(__('lite-crm::organisations.fields.phone'))->tel()->maxLength(50),
                TextInput::make('country')->label(__('lite-crm::organisations.fields.country'))->maxLength(100),
                TextInput::make('region')->label(__('lite-crm::organisations.fields.region'))->maxLength(255),
                TextInput::make('city')->label(__('lite-crm::organisations.fields.city'))->maxLength(255),
                Textarea::make('address')->label(__('lite-crm::organisations.fields.address'))->columnSpanFull(),
            ]),
            ...CustomFieldComponents::form('organisation', $typeKey),
            Section::make(__('lite-crm::organisations.sections.public_naming'))
                ->description(__('lite-crm::organisations.sections.public_naming_help'))
                ->columns(2)
                ->collapsible()
                ->schema([
                    Toggle::make('permission_to_name_publicly')
                        ->label(__('lite-crm::organisations.fields.permission_to_name_publicly'))
                        ->live()
                        ->columnSpanFull(),
                    DatePicker::make('permission_granted_on')
                        ->label(__('lite-crm::organisations.fields.permission_granted_on'))
                        ->required(fn (Get $get): bool => (bool) $get('permission_to_name_publicly'))
                        ->visible(fn (Get $get): bool => (bool) $get('permission_to_name_publicly')),
                    Select::make('permission_document_id')
                        ->label(__('lite-crm::organisations.fields.permission_document'))
                        ->helperText(__('lite-crm::organisations.fields.permission_document_help'))
                        ->options(fn (?Model $record): array => $record instanceof Organisation
                            ? $record->documents()->pluck('title', 'id')->all()
                            : [])
                        ->visible(fn (Get $get): bool => (bool) $get('permission_to_name_publicly')),
                ]),
            Section::make(__('lite-crm::organisations.fields.notes'))->collapsible()->schema([
                Textarea::make('notes')->hiddenLabel()->rows(4),
            ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make(__('lite-crm::organisations.sections.details'))->columns(3)->schema([
                TextEntry::make('type.label')->label(__('lite-crm::organisations.fields.type'))->badge()->placeholder('—'),
                TextEntry::make('status.label')->label(__('lite-crm::organisations.fields.status'))->badge()->placeholder('—'),
                TextEntry::make('territory.label')->label(__('lite-crm::organisations.fields.territory'))->placeholder('—'),
                TextEntry::make('owner.name')->label(__('lite-crm::common.fields.owner'))->placeholder('—'),
                TextEntry::make('source')->label(__('lite-crm::organisations.fields.source'))->placeholder('—'),
                TextEntry::make('tags.name')->label(__('lite-crm::common.fields.tags'))->badge()->placeholder('—'),
            ]),
            Section::make(__('lite-crm::organisations.sections.contact'))->columns(3)->schema([
                TextEntry::make('website')->label(__('lite-crm::organisations.fields.website'))->url(fn (Organisation $record): ?string => $record->website, shouldOpenInNewTab: true)->placeholder('—'),
                TextEntry::make('email')->label(__('lite-crm::organisations.fields.email'))->placeholder('—'),
                TextEntry::make('phone')->label(__('lite-crm::organisations.fields.phone'))->placeholder('—'),
                TextEntry::make('city')->label(__('lite-crm::organisations.fields.city'))->placeholder('—'),
                TextEntry::make('region')->label(__('lite-crm::organisations.fields.region'))->placeholder('—'),
                TextEntry::make('country')->label(__('lite-crm::organisations.fields.country'))->placeholder('—'),
                TextEntry::make('address')->label(__('lite-crm::organisations.fields.address'))->placeholder('—')->columnSpanFull(),
            ]),
            ...CustomFieldComponents::infolist('organisation'),
            Section::make(__('lite-crm::organisations.sections.public_naming'))->columns(3)->collapsible()->schema([
                IconEntry::make('permission_to_name_publicly')->label(__('lite-crm::organisations.fields.permission_to_name_publicly'))->boolean(),
                TextEntry::make('permission_granted_on')->label(__('lite-crm::organisations.fields.permission_granted_on'))->date()->placeholder('—'),
                TextEntry::make('permissionDocument.title')->label(__('lite-crm::organisations.fields.permission_document'))->placeholder('—'),
            ]),
            Section::make(__('lite-crm::organisations.fields.notes'))->collapsible()->schema([
                TextEntry::make('notes')->hiddenLabel()->placeholder('—'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label(__('lite-crm::organisations.fields.name'))->searchable()->sortable(),
                TextColumn::make('type.label')->label(__('lite-crm::organisations.fields.type'))->badge(),
                TextColumn::make('status.label')->label(__('lite-crm::organisations.fields.status'))->badge()->toggleable(),
                TextColumn::make('city')->label(__('lite-crm::organisations.fields.city'))->searchable()->toggleable(),
                TextColumn::make('country')->label(__('lite-crm::organisations.fields.country'))->toggleable(),
                TextColumn::make('territory.label')->label(__('lite-crm::organisations.fields.territory'))->toggleable(),
                TextColumn::make('owner.name')->label(__('lite-crm::common.fields.owner'))->toggleable(),
                TextColumn::make('tags.name')->label(__('lite-crm::common.fields.tags'))->badge()->toggleable(isToggledHiddenByDefault: true),
                ...CustomFieldComponents::tableColumns('organisation'),
            ])
            ->defaultSort('name')
            ->filters([
                SelectFilter::make('type_id')->label(__('lite-crm::organisations.fields.type'))->options(fn (): array => Lookup::options('organisation_type')),
                SelectFilter::make('status_id')->label(__('lite-crm::organisations.fields.status'))->options(fn (): array => Lookup::options('organisation_status')),
                SelectFilter::make('territory_id')->label(__('lite-crm::organisations.fields.territory'))->options(fn (): array => Lookup::options('territory')),
                SelectFilter::make('owner_id')->label(__('lite-crm::common.fields.owner'))->options(fn (): array => LiteCrm::userOptions()),
                SelectFilter::make('tags')->label(__('lite-crm::common.fields.tags'))->relationship('tags', 'name')->multiple(),
                ...CustomFieldComponents::tableFilters('organisation'),
                TrashedFilter::make(),
            ])
            ->recordActions([ViewAction::make(), EditAction::make(), DeleteAction::make(), RestoreAction::make()]);
    }

    public static function getRelations(): array
    {
        return array_values(array_filter([
            LiteCrm::isModuleEnabled('contacts') ? RelationManagers\ContactsRelationManager::class : null,
            LiteCrm::isModuleEnabled('activities') ? ActivitiesRelationManager::class : null,
            LiteCrm::isModuleEnabled('tasks') ? TasksRelationManager::class : null,
            LiteCrm::isModuleEnabled('documents') ? DocumentsRelationManager::class : null,
        ]));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrganisations::route('/'),
            'create' => Pages\CreateOrganisation::route('/create'),
            'view' => Pages\ViewOrganisation::route('/{record}'),
            'edit' => Pages\EditOrganisation::route('/{record}/edit'),
        ];
    }
}
