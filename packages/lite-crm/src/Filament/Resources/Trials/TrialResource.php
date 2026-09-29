<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Resources\Trials;

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
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use LiteCrm\CustomFields\CustomFieldComponents;
use LiteCrm\Enums\TrialStatus;
use LiteCrm\Filament\Resources\RecordResource;
use LiteCrm\Filament\Resources\Shared\ActivitiesRelationManager;
use LiteCrm\Filament\Resources\Shared\DocumentsRelationManager;
use LiteCrm\Filament\Resources\Shared\TasksRelationManager;
use LiteCrm\Filament\Support\Fields;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Trial;

class TrialResource extends RecordResource
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?int $navigationSort = 65;

    protected static ?string $slug = 'trials';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModel(): string
    {
        return LiteCrm::model(Trial::class);
    }

    public static function getModelLabel(): string
    {
        return __('lite-crm::trials.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('lite-crm::trials.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make(__('lite-crm::trials.sections.trial'))->columns(2)->schema([
                TextInput::make('name')->label(__('lite-crm::trials.fields.name'))->required()->maxLength(255)->columnSpanFull(),
                Fields::organisation(),
                Fields::product(),
                Select::make('status')->label(__('lite-crm::trials.fields.status'))->options(TrialStatus::class)->default(TrialStatus::Planned)->required(),
                Fields::owner(),
                DatePicker::make('start_date')->label(__('lite-crm::trials.fields.start_date')),
                DatePicker::make('end_date')->label(__('lite-crm::trials.fields.end_date'))->afterOrEqual('start_date'),
                Textarea::make('protocol')->label(__('lite-crm::trials.fields.protocol'))->helperText(__('lite-crm::trials.fields.protocol_help'))->rows(4)->columnSpanFull(),
                Textarea::make('result_summary')->label(__('lite-crm::trials.fields.result_summary'))->rows(4)->columnSpanFull(),
            ]),
            Section::make(__('lite-crm::trials.sections.consent'))
                ->description(__('lite-crm::trials.sections.consent_help'))
                ->columns(2)
                ->schema([
                    Toggle::make('consent_to_publish')->label(__('lite-crm::trials.fields.consent_to_publish'))->live(),
                    DatePicker::make('consent_date')
                        ->label(__('lite-crm::trials.fields.consent_date'))
                        ->required(fn (Get $get): bool => (bool) $get('consent_to_publish'))
                        ->visible(fn (Get $get): bool => (bool) $get('consent_to_publish')),
                ]),
            ...CustomFieldComponents::form('trial'),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make(__('lite-crm::trials.sections.trial'))->columns(3)->schema([
                TextEntry::make('organisation.name')->label(__('lite-crm::organisations.label'))->placeholder('—'),
                TextEntry::make('product.name')->label(__('lite-crm::products.label'))->placeholder('—'),
                TextEntry::make('status')->label(__('lite-crm::trials.fields.status'))->badge(),
                TextEntry::make('start_date')->label(__('lite-crm::trials.fields.start_date'))->date()->placeholder('—'),
                TextEntry::make('end_date')->label(__('lite-crm::trials.fields.end_date'))->date()->placeholder('—'),
                TextEntry::make('owner.name')->label(__('lite-crm::common.fields.owner'))->placeholder('—'),
                TextEntry::make('protocol')->label(__('lite-crm::trials.fields.protocol'))->placeholder('—')->columnSpanFull(),
                TextEntry::make('result_summary')->label(__('lite-crm::trials.fields.result_summary'))->placeholder('—')->columnSpanFull(),
            ]),
            Section::make(__('lite-crm::trials.sections.consent'))->columns(2)->schema([
                IconEntry::make('consent_to_publish')->label(__('lite-crm::trials.fields.consent_to_publish'))->boolean(),
                TextEntry::make('consent_date')->label(__('lite-crm::trials.fields.consent_date'))->date()->placeholder('—'),
            ]),
            ...CustomFieldComponents::infolist('trial'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label(__('lite-crm::trials.fields.name'))->searchable()->wrap(),
                TextColumn::make('organisation.name')->label(__('lite-crm::organisations.label')),
                TextColumn::make('product.name')->label(__('lite-crm::products.label'))->toggleable(),
                TextColumn::make('status')->label(__('lite-crm::trials.fields.status'))->badge(),
                TextColumn::make('start_date')->label(__('lite-crm::trials.fields.start_date'))->date()->sortable(),
                IconColumn::make('consent_to_publish')->label(__('lite-crm::trials.fields.consent_short'))->boolean()->toggleable(),
                ...CustomFieldComponents::tableColumns('trial'),
            ])
            ->defaultSort('start_date', 'desc')
            ->filters([
                SelectFilter::make('status')->label(__('lite-crm::trials.fields.status'))->options(TrialStatus::class),
                ...CustomFieldComponents::tableFilters('trial'),
                TrashedFilter::make(),
            ])
            ->recordActions([ViewAction::make(), EditAction::make(), DeleteAction::make(), RestoreAction::make()]);
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
            'index' => Pages\ListTrials::route('/'),
            'create' => Pages\CreateTrial::route('/create'),
            'view' => Pages\ViewTrial::route('/{record}'),
            'edit' => Pages\EditTrial::route('/{record}/edit'),
        ];
    }
}
