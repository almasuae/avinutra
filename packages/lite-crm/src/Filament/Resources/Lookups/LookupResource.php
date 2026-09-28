<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Resources\Lookups;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;
use LiteCrm\Filament\Resources\CrmResource;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Lookup;

class LookupResource extends CrmResource
{
    protected static ?string $viewPermission = 'lookups.view';

    protected static ?string $managePermission = 'lookups.manage';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedListBullet;

    protected static ?int $navigationSort = 20;

    public static function getModel(): string
    {
        return LiteCrm::model(Lookup::class);
    }

    public static function getModelLabel(): string
    {
        return __('lite-crm::lookups.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('lite-crm::lookups.plural');
    }

    /**
     * @return array<string, string>
     */
    public static function typeOptions(): array
    {
        $options = [];

        /** @var list<string> $types */
        $types = config('lite-crm.lookup_types', []);

        foreach ($types as $type) {
            $options[$type] = Lookup::typeLabel($type);
        }

        return $options;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('type')
                ->label(__('lite-crm::lookups.fields.type'))
                ->options(static::typeOptions())
                ->required()
                ->live()
                ->disabledOn('edit'),
            TextInput::make('label')
                ->label(__('lite-crm::lookups.fields.label'))
                ->required()
                ->maxLength(255),
            TextInput::make('key')
                ->label(__('lite-crm::lookups.fields.key'))
                ->helperText(__('lite-crm::lookups.fields.key_help'))
                ->required()
                ->maxLength(100)
                ->regex('/^[a-z0-9][a-z0-9_.-]*$/')
                ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule, Get $get): Unique => $rule->where('type', $get('type')))
                ->disabledOn('edit'),
            TextInput::make('sort')
                ->label(__('lite-crm::lookups.fields.sort'))
                ->integer()
                ->minValue(0)
                ->default(0),
            Toggle::make('is_active')
                ->label(__('lite-crm::lookups.fields.is_active'))
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')
                    ->label(__('lite-crm::lookups.fields.type'))
                    ->formatStateUsing(fn (string $state): string => Lookup::typeLabel($state))
                    ->badge()
                    ->sortable(),
                TextColumn::make('label')->label(__('lite-crm::lookups.fields.label'))->searchable()->sortable(),
                TextColumn::make('key')->label(__('lite-crm::lookups.fields.key'))->searchable()->toggleable(),
                TextColumn::make('sort')->label(__('lite-crm::lookups.fields.sort'))->numeric()->sortable(),
                ToggleColumn::make('is_active')
                    ->label(__('lite-crm::lookups.fields.is_active'))
                    ->disabled(fn (): bool => ! static::canEdit(new Lookup)),
            ])
            ->defaultSort(fn ($query) => $query->orderBy('type')->orderBy('sort')->orderBy('label'))
            ->filters([
                SelectFilter::make('type')->label(__('lite-crm::lookups.fields.type'))->options(static::typeOptions()),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageLookups::route('/'),
        ];
    }
}
