<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Resources\Tags;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use LiteCrm\Filament\Resources\CrmResource;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Tag;

class TagResource extends CrmResource
{
    protected static ?string $viewPermission = 'lookups.view';

    protected static ?string $managePermission = 'lookups.manage';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?int $navigationSort = 40;

    public static function getModel(): string
    {
        return LiteCrm::model(Tag::class);
    }

    public static function getModelLabel(): string
    {
        return __('lite-crm::tags.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('lite-crm::tags.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label(__('lite-crm::tags.fields.name'))->required()->maxLength(255),
            TextInput::make('slug')
                ->label(__('lite-crm::tags.fields.slug'))
                ->helperText(__('lite-crm::tags.fields.slug_help'))
                ->maxLength(255)
                ->alphaDash()
                ->unique(ignoreRecord: true),
            ColorPicker::make('color')->label(__('lite-crm::tags.fields.color')),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ColorColumn::make('color')->label(__('lite-crm::tags.fields.color')),
                TextColumn::make('name')->label(__('lite-crm::tags.fields.name'))->searchable()->sortable(),
                TextColumn::make('slug')->label(__('lite-crm::tags.fields.slug'))->toggleable(),
            ])
            ->defaultSort('name')
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageTags::route('/'),
        ];
    }
}
