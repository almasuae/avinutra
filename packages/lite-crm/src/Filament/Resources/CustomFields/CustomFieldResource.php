<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Resources\CustomFields;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;
use LiteCrm\CustomFields\CustomFieldRegistry;
use LiteCrm\CustomFields\CustomFieldType;
use LiteCrm\Filament\Resources\CrmResource;
use LiteCrm\LiteCrm;
use LiteCrm\Models\CustomField;
use LiteCrm\Models\Lookup;

class CustomFieldResource extends CrmResource
{
    protected static ?string $viewPermission = 'custom_fields.manage';

    protected static ?string $managePermission = 'custom_fields.manage';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static ?int $navigationSort = 50;

    public static function getModel(): string
    {
        return LiteCrm::model(CustomField::class);
    }

    public static function getModelLabel(): string
    {
        return __('lite-crm::custom-fields.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('lite-crm::custom-fields.plural');
    }

    /**
     * The lookup type that classifies records of an entity, for "visible_for_types".
     */
    public static function typeLookupFor(?string $entity): ?string
    {
        /** @var array<string, string> $map */
        $map = config('lite-crm.custom_field_type_lookups', []);

        return $entity !== null ? ($map[$entity] ?? null) : null;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            Select::make('entity')
                ->label(__('lite-crm::custom-fields.fields.entity'))
                ->options(CustomFieldRegistry::entityOptions())
                ->required()
                ->live()
                ->disabledOn('edit'),
            Select::make('type')
                ->label(__('lite-crm::custom-fields.fields.type'))
                ->options(CustomFieldType::options())
                ->required()
                ->live(),
            TextInput::make('label')->label(__('lite-crm::custom-fields.fields.label'))->required()->maxLength(255),
            TextInput::make('key')
                ->label(__('lite-crm::custom-fields.fields.key'))
                ->helperText(__('lite-crm::custom-fields.fields.key_help'))
                ->required()
                ->maxLength(100)
                ->regex('/^[a-z][a-z0-9_]*$/')
                ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule, Get $get): Unique => $rule->where('entity', $get('entity')))
                ->disabledOn('edit'),
            TextInput::make('section')
                ->label(__('lite-crm::custom-fields.fields.section'))
                ->helperText(__('lite-crm::custom-fields.fields.section_help'))
                ->maxLength(255),
            TextInput::make('help_text')->label(__('lite-crm::custom-fields.fields.help_text'))->maxLength(255),
            Repeater::make('options')
                ->label(__('lite-crm::custom-fields.fields.options'))
                ->schema([
                    TextInput::make('value')->label(__('lite-crm::custom-fields.fields.option_value'))->required()->maxLength(100)->distinct(),
                    TextInput::make('label')->label(__('lite-crm::custom-fields.fields.option_label'))->required()->maxLength(255),
                ])
                ->columns(2)
                ->columnSpanFull()
                ->minItems(1)
                ->visible(fn (Get $get): bool => CustomFieldType::tryFrom((string) $get('type'))?->hasOptions() ?? false),
            Select::make('visible_for_types')
                ->label(__('lite-crm::custom-fields.fields.visible_for_types'))
                ->helperText(__('lite-crm::custom-fields.fields.visible_for_types_help'))
                ->multiple()
                ->options(fn (Get $get): array => ($lookup = static::typeLookupFor($get('entity'))) !== null ? Lookup::options($lookup, 'key') : [])
                ->visible(fn (Get $get): bool => static::typeLookupFor($get('entity')) !== null)
                ->columnSpanFull(),
            Section::make(__('lite-crm::custom-fields.sections.behaviour'))
                ->columns(4)
                ->columnSpanFull()
                ->schema([
                    Toggle::make('required')->label(__('lite-crm::custom-fields.fields.required')),
                    Toggle::make('show_in_table')->label(__('lite-crm::custom-fields.fields.show_in_table')),
                    Toggle::make('filterable')
                        ->label(__('lite-crm::custom-fields.fields.filterable'))
                        ->helperText(__('lite-crm::custom-fields.fields.filterable_help')),
                    Toggle::make('is_active')->label(__('lite-crm::custom-fields.fields.is_active'))->default(true),
                    TextInput::make('sort')->label(__('lite-crm::custom-fields.fields.sort'))->integer()->minValue(0)->default(0),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('entity')
                    ->label(__('lite-crm::custom-fields.fields.entity'))
                    ->formatStateUsing(fn (string $state): string => CustomFieldRegistry::entityOptions()[$state] ?? $state)
                    ->badge()
                    ->sortable(),
                TextColumn::make('label')->label(__('lite-crm::custom-fields.fields.label'))->searchable()->sortable(),
                TextColumn::make('key')->label(__('lite-crm::custom-fields.fields.key'))->searchable()->toggleable(),
                TextColumn::make('type')
                    ->label(__('lite-crm::custom-fields.fields.type'))
                    ->formatStateUsing(fn (CustomFieldType $state): string => $state->label()),
                TextColumn::make('section')->label(__('lite-crm::custom-fields.fields.section'))->toggleable(),
                IconColumn::make('required')->label(__('lite-crm::custom-fields.fields.required'))->boolean(),
                IconColumn::make('is_active')->label(__('lite-crm::custom-fields.fields.is_active'))->boolean(),
                TextColumn::make('sort')->label(__('lite-crm::custom-fields.fields.sort'))->sortable(),
            ])
            ->defaultSort(fn ($query) => $query->orderBy('entity')->orderBy('sort'))
            ->filters([
                SelectFilter::make('entity')->label(__('lite-crm::custom-fields.fields.entity'))->options(CustomFieldRegistry::entityOptions()),
                SelectFilter::make('type')->label(__('lite-crm::custom-fields.fields.type'))->options(CustomFieldType::options()),
                TrashedFilter::make(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make(), RestoreAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCustomFields::route('/'),
        ];
    }
}
