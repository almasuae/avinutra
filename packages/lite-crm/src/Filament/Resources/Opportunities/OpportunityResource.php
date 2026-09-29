<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Resources\Opportunities;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use LiteCrm\CustomFields\CustomFieldComponents;
use LiteCrm\Filament\Resources\RecordResource;
use LiteCrm\Filament\Resources\Shared\ActivitiesRelationManager;
use LiteCrm\Filament\Resources\Shared\DocumentsRelationManager;
use LiteCrm\Filament\Resources\Shared\TasksRelationManager;
use LiteCrm\Filament\Support\Fields;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Lookup;
use LiteCrm\Models\Opportunity;
use LiteCrm\Models\Pipeline;
use LiteCrm\Models\PipelineStage;

class OpportunityResource extends RecordResource
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    protected static ?int $navigationSort = 15;

    protected static ?string $slug = 'opportunities';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModel(): string
    {
        return LiteCrm::model(Opportunity::class);
    }

    public static function getModelLabel(): string
    {
        return __('lite-crm::opportunities.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('lite-crm::opportunities.plural');
    }

    /**
     * Active pipelines the signed-in user may work in.
     *
     * @return array<int, string>
     */
    public static function pipelineOptions(): array
    {
        /** @var array<int, string> $options */
        $options = LiteCrm::model(Pipeline::class)::query()
            ->where('is_active', true)
            ->scopes(['visibleTo' => [Filament::auth()->user()]])
            ->orderBy('sort')
            ->pluck('name', 'id')
            ->all();

        return $options;
    }

    /**
     * @return array<int, string>
     */
    public static function stageOptions(mixed $pipelineId): array
    {
        if (blank($pipelineId)) {
            return [];
        }

        /** @var array<int, string> $options */
        $options = LiteCrm::model(PipelineStage::class)::query()
            ->where('pipeline_id', $pipelineId)
            ->orderBy('sort')
            ->pluck('name', 'id')
            ->all();

        return $options;
    }

    public static function isLostStage(mixed $stageId): bool
    {
        return filled($stageId) && (bool) LiteCrm::model(PipelineStage::class)::query()->whereKey($stageId)->value('is_lost');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make(__('lite-crm::opportunities.sections.deal'))->columns(2)->schema([
                TextInput::make('name')->label(__('lite-crm::opportunities.fields.name'))->required()->maxLength(255)->columnSpanFull(),
                Select::make('pipeline_id')
                    ->label(__('lite-crm::opportunities.fields.pipeline'))
                    ->options(fn (): array => static::pipelineOptions())
                    ->default(fn (): mixed => array_key_first(static::pipelineOptions()))
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn (Set $set, mixed $state) => $set('stage_id', array_key_first(static::stageOptions($state)))),
                Select::make('stage_id')
                    ->label(__('lite-crm::opportunities.fields.stage'))
                    ->options(fn (Get $get): array => static::stageOptions($get('pipeline_id')))
                    ->default(fn (Get $get): mixed => array_key_first(static::stageOptions($get('pipeline_id'))))
                    ->required()
                    ->live(),
                Fields::lookup('lost_reason_id', 'lost_reason', __('lite-crm::opportunities.fields.lost_reason'))
                    ->visible(fn (Get $get): bool => static::isLostStage($get('stage_id')))
                    ->required(fn (Get $get): bool => static::isLostStage($get('stage_id'))),
                Fields::organisation(),
                Fields::contact(),
                Fields::product(),
                Fields::owner(),
                Fields::tags(),
            ]),
            Section::make(__('lite-crm::opportunities.sections.value'))->columns(3)->schema([
                TextInput::make('volume')->label(__('lite-crm::opportunities.fields.volume'))->numeric()->minValue(0),
                TextInput::make('unit')->label(__('lite-crm::opportunities.fields.unit'))->maxLength(30),
                TextInput::make('probability')
                    ->label(__('lite-crm::opportunities.fields.probability'))
                    ->helperText(__('lite-crm::opportunities.fields.probability_help'))
                    ->integer()
                    ->minValue(0)
                    ->maxValue(100)
                    ->suffix('%')
                    ->hiddenOn('create'),
                TextInput::make('value')->label(__('lite-crm::opportunities.fields.value'))->numeric()->minValue(0),
                Fields::currency(),
                DatePicker::make('expected_close_date')->label(__('lite-crm::opportunities.fields.expected_close_date')),
            ]),
            Section::make(__('lite-crm::opportunities.sections.next'))->columns(2)->schema([
                TextInput::make('next_step')->label(__('lite-crm::opportunities.fields.next_step'))->maxLength(255),
                DatePicker::make('next_step_date')->label(__('lite-crm::opportunities.fields.next_step_date')),
                Textarea::make('notes')->label(__('lite-crm::opportunities.fields.notes'))->columnSpanFull(),
            ]),
            ...CustomFieldComponents::form('opportunity', fn (Get $get): ?string => LiteCrm::model(Pipeline::class)::query()->whereKey($get('pipeline_id'))->value('key')),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make(__('lite-crm::opportunities.sections.deal'))->columns(3)->schema([
                TextEntry::make('pipeline.name')->label(__('lite-crm::opportunities.fields.pipeline')),
                TextEntry::make('stage.name')->label(__('lite-crm::opportunities.fields.stage'))->badge(),
                TextEntry::make('lostReason.label')->label(__('lite-crm::opportunities.fields.lost_reason'))
                    ->visible(fn (Opportunity $record): bool => $record->lost_reason_id !== null),
                TextEntry::make('organisation.name')->label(__('lite-crm::organisations.label'))->placeholder('—'),
                TextEntry::make('contact.name')->label(__('lite-crm::contacts.label'))->placeholder('—'),
                TextEntry::make('product.name')->label(__('lite-crm::products.label'))->placeholder('—'),
                TextEntry::make('owner.name')->label(__('lite-crm::common.fields.owner'))->placeholder('—'),
                TextEntry::make('tags.name')->label(__('lite-crm::common.fields.tags'))->badge()->placeholder('—'),
            ]),
            Section::make(__('lite-crm::opportunities.sections.value'))->columns(3)->schema([
                TextEntry::make('volume')->label(__('lite-crm::opportunities.fields.volume'))
                    ->formatStateUsing(fn (Opportunity $record): string => trim($record->volume.' '.$record->unit))->placeholder('—'),
                TextEntry::make('value')->label(__('lite-crm::opportunities.fields.value'))
                    ->formatStateUsing(fn (Opportunity $record): string => trim(($record->currency ?? '').' '.number_format((float) $record->value, 2)))->placeholder('—'),
                TextEntry::make('probability')->label(__('lite-crm::opportunities.fields.probability'))->suffix('%'),
                TextEntry::make('weighted')->label(__('lite-crm::opportunities.fields.weighted_value'))
                    ->state(fn (Opportunity $record): ?string => $record->weightedValue() === null ? null : trim(($record->currency ?? '').' '.number_format((float) $record->weightedValue(), 2)))
                    ->placeholder('—'),
                TextEntry::make('expected_close_date')->label(__('lite-crm::opportunities.fields.expected_close_date'))->date()->placeholder('—'),
                TextEntry::make('closed_at')->label(__('lite-crm::opportunities.fields.closed_at'))->dateTime()->placeholder('—'),
            ]),
            Section::make(__('lite-crm::opportunities.sections.next'))->columns(2)->schema([
                TextEntry::make('next_step')->label(__('lite-crm::opportunities.fields.next_step'))->placeholder('—'),
                TextEntry::make('next_step_date')->label(__('lite-crm::opportunities.fields.next_step_date'))->date()->placeholder('—'),
                TextEntry::make('notes')->label(__('lite-crm::opportunities.fields.notes'))->placeholder('—')->columnSpanFull(),
            ]),
            ...CustomFieldComponents::infolist('opportunity'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['pipeline', 'stage', 'organisation', 'owner']))
            ->columns([
                TextColumn::make('name')->label(__('lite-crm::opportunities.fields.name'))->searchable()->sortable()->wrap(),
                TextColumn::make('organisation.name')->label(__('lite-crm::organisations.label'))->toggleable(),
                TextColumn::make('pipeline.name')->label(__('lite-crm::opportunities.fields.pipeline'))->toggleable(),
                TextColumn::make('stage.name')->label(__('lite-crm::opportunities.fields.stage'))->badge(),
                TextColumn::make('value')
                    ->label(__('lite-crm::opportunities.fields.value'))
                    ->formatStateUsing(fn (Opportunity $record): string => trim(($record->currency ?? '').' '.number_format((float) $record->value, 2)))
                    ->sortable(),
                TextColumn::make('probability')->label(__('lite-crm::opportunities.fields.probability'))->suffix('%')->sortable()->toggleable(),
                TextColumn::make('expected_close_date')->label(__('lite-crm::opportunities.fields.expected_close_date'))->date()->sortable(),
                TextColumn::make('owner.name')->label(__('lite-crm::common.fields.owner'))->toggleable(),
                ...CustomFieldComponents::tableColumns('opportunity'),
            ])
            ->defaultSort('expected_close_date')
            ->filters([
                SelectFilter::make('pipeline_id')->label(__('lite-crm::opportunities.fields.pipeline'))->options(fn (): array => static::pipelineOptions()),
                TernaryFilter::make('open')
                    ->label(__('lite-crm::opportunities.filters.open'))
                    ->default(true)
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereNull('closed_at'),
                        false: fn (Builder $query): Builder => $query->whereNotNull('closed_at'),
                    ),
                SelectFilter::make('owner_id')->label(__('lite-crm::common.fields.owner'))->options(fn (): array => LiteCrm::userOptions()),
                SelectFilter::make('lost_reason_id')->label(__('lite-crm::opportunities.fields.lost_reason'))->options(fn (): array => Lookup::options('lost_reason')),
                ...CustomFieldComponents::tableFilters('opportunity'),
                TrashedFilter::make(),
            ])
            ->recordActions([ViewAction::make(), EditAction::make(), DeleteAction::make(), RestoreAction::make()]);
    }

    public static function getRelations(): array
    {
        return array_values(array_filter([
            LiteCrm::isModuleEnabled('activities') ? ActivitiesRelationManager::class : null,
            LiteCrm::isModuleEnabled('tasks') ? TasksRelationManager::class : null,
            LiteCrm::isModuleEnabled('quotations') ? RelationManagers\QuotationsRelationManager::class : null,
            LiteCrm::isModuleEnabled('documents') ? DocumentsRelationManager::class : null,
        ]));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOpportunities::route('/'),
            'create' => Pages\CreateOpportunity::route('/create'),
            'view' => Pages\ViewOpportunity::route('/{record}'),
            'edit' => Pages\EditOpportunity::route('/{record}/edit'),
        ];
    }
}
