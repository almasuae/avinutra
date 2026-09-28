<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Resources\Pipelines;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use LiteCrm\Filament\Resources\CrmResource;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Pipeline;

class PipelineResource extends CrmResource
{
    protected static ?string $viewPermission = 'lookups.view';

    protected static ?string $managePermission = 'lookups.manage';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFunnel;

    protected static ?int $navigationSort = 30;

    public static function getModel(): string
    {
        return LiteCrm::model(Pipeline::class);
    }

    public static function getModelLabel(): string
    {
        return __('lite-crm::pipelines.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('lite-crm::pipelines.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('name')->label(__('lite-crm::pipelines.fields.name'))->required()->maxLength(255),
            TextInput::make('key')
                ->label(__('lite-crm::pipelines.fields.key'))
                ->required()
                ->maxLength(100)
                ->regex('/^[a-z0-9][a-z0-9_.-]*$/')
                ->unique(ignoreRecord: true)
                ->disabledOn('edit'),
            Textarea::make('description')->label(__('lite-crm::pipelines.fields.description'))->rows(2)->columnSpanFull(),
            TextInput::make('sort')->label(__('lite-crm::pipelines.fields.sort'))->integer()->minValue(0)->default(0),
            Toggle::make('is_active')->label(__('lite-crm::pipelines.fields.is_active'))->default(true),
            Repeater::make('stages')
                ->label(__('lite-crm::pipelines.fields.stages'))
                ->relationship('stages')
                ->orderColumn('sort')
                ->columns(5)
                ->columnSpanFull()
                ->minItems(1)
                ->schema([
                    TextInput::make('name')->label(__('lite-crm::pipelines.fields.stage_name'))->required()->maxLength(255)->columnSpan(2),
                    TextInput::make('key')
                        ->label(__('lite-crm::pipelines.fields.key'))
                        ->required()
                        ->maxLength(100)
                        ->regex('/^[a-z0-9][a-z0-9_.-]*$/')
                        ->distinct(),
                    TextInput::make('probability')
                        ->label(__('lite-crm::pipelines.fields.probability'))
                        ->integer()
                        ->minValue(0)
                        ->maxValue(100)
                        ->suffix('%')
                        ->default(0),
                    Toggle::make('is_won')->label(__('lite-crm::pipelines.fields.is_won')),
                    Toggle::make('is_lost')->label(__('lite-crm::pipelines.fields.is_lost')),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label(__('lite-crm::pipelines.fields.name'))->searchable()->sortable(),
                TextColumn::make('key')->label(__('lite-crm::pipelines.fields.key'))->toggleable(),
                TextColumn::make('stages_count')->label(__('lite-crm::pipelines.fields.stages'))->counts('stages'),
                TextColumn::make('sort')->label(__('lite-crm::pipelines.fields.sort'))->sortable(),
                IconColumn::make('is_active')->label(__('lite-crm::pipelines.fields.is_active'))->boolean(),
            ])
            ->defaultSort('sort')
            ->filters([TrashedFilter::make()])
            ->recordActions([EditAction::make(), DeleteAction::make(), RestoreAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManagePipelines::route('/'),
        ];
    }
}
