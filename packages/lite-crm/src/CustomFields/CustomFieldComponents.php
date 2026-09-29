<?php

declare(strict_types=1);

namespace LiteCrm\CustomFields;

use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\BaseFilter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Database\Eloquent\Builder;
use LiteCrm\LiteCrm;
use LiteCrm\Models\CustomField;

/**
 * Builds Filament form fields, table columns, filters and infolist entries from
 * the custom-field definitions, so new fields appear without code changes.
 *
 * Values are read from and written to the record's "custom" JSON column.
 */
class CustomFieldComponents
{
    /**
     * Form sections for an entity, one per definition "section".
     *
     * @param  (Closure(Get): ?string)|null  $typeKey  Returns the record's current type key from the form state,
     *                                                 used for "visible_for_types".
     * @return array<int, Section>
     */
    public static function form(string $entity, ?Closure $typeKey = null): array
    {
        $sections = [];

        foreach (static::grouped($entity) as $section => $fields) {
            $components = array_map(fn (CustomField $field): Field => static::field($field, $typeKey), $fields);

            $sections[] = Section::make($section)
                ->schema($components)
                ->columns(2)
                ->visible(function (Get $get) use ($fields, $typeKey): bool {
                    $key = $typeKey?->__invoke($get);

                    foreach ($fields as $field) {
                        if ($field->appliesToType($key)) {
                            return true;
                        }
                    }

                    return false;
                });
        }

        return $sections;
    }

    /**
     * @param  (Closure(Get): ?string)|null  $typeKey
     */
    public static function field(CustomField $field, ?Closure $typeKey = null): Field
    {
        $name = 'custom.'.$field->key;

        $component = match ($field->type) {
            CustomFieldType::Text => TextInput::make($name)->maxLength(255),
            CustomFieldType::Textarea => Textarea::make($name)->rows(3)->columnSpanFull(),
            CustomFieldType::Number => TextInput::make($name)->integer(),
            CustomFieldType::Decimal => TextInput::make($name)->numeric(),
            CustomFieldType::Currency => TextInput::make($name)->numeric()->prefix(LiteCrm::baseCurrency()),
            CustomFieldType::Percentage => TextInput::make($name)->numeric()->minValue(0)->maxValue(100)->suffix('%'),
            CustomFieldType::Date => DatePicker::make($name),
            CustomFieldType::Boolean => Toggle::make($name),
            CustomFieldType::Select => Select::make($name)->options($field->optionMap()),
            CustomFieldType::Multiselect => Select::make($name)->multiple()->options($field->optionMap()),
            CustomFieldType::Url => TextInput::make($name)->url()->maxLength(2048),
        };

        $component
            ->label($field->label)
            ->helperText($field->help_text)
            ->required($field->required)
            ->rules(CustomFieldValidator::rulesFor($field)['']);

        if ($typeKey !== null && filled($field->visible_for_types)) {
            $component->visible(fn (Get $get): bool => $field->appliesToType($typeKey($get)));
        }

        return $component;
    }

    /**
     * Columns for definitions marked "show in table" (toggleable).
     *
     * @return array<int, Column>
     */
    public static function tableColumns(string $entity): array
    {
        $columns = [];

        foreach (app(CustomFieldRegistry::class)->for($entity)->where('show_in_table', true) as $field) {
            $name = 'custom.'.$field->key;

            $column = match ($field->type) {
                CustomFieldType::Boolean => IconColumn::make($name)->boolean(),
                CustomFieldType::Date => TextColumn::make($name)->date(),
                CustomFieldType::Select, CustomFieldType::Multiselect => TextColumn::make($name)
                    ->badge()
                    ->formatStateUsing(fn (mixed $state): string => $field->optionMap()[(string) $state] ?? (string) $state),
                CustomFieldType::Percentage => TextColumn::make($name)->numeric()->suffix('%'),
                CustomFieldType::Number, CustomFieldType::Decimal, CustomFieldType::Currency => TextColumn::make($name)->numeric(),
                default => TextColumn::make($name)->limit(50),
            };

            $columns[] = $column->label($field->label)->toggleable();
        }

        return $columns;
    }

    /**
     * Filters for definitions marked "filterable" (select, multiselect and boolean types).
     *
     * @return array<int, BaseFilter>
     */
    public static function tableFilters(string $entity): array
    {
        $filters = [];

        foreach (app(CustomFieldRegistry::class)->for($entity)->where('filterable', true) as $field) {
            $path = 'custom->'.$field->key;
            $name = 'custom_'.$field->key;

            $filter = match ($field->type) {
                CustomFieldType::Select => SelectFilter::make($name)
                    ->options($field->optionMap())
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->where($path, $data['value'])
                        : $query),
                CustomFieldType::Multiselect => SelectFilter::make($name)
                    ->options($field->optionMap())
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->whereJsonContains($path, $data['value'])
                        : $query),
                CustomFieldType::Boolean => TernaryFilter::make($name)
                    ->queries(
                        true: fn (Builder $query): Builder => $query->where($path, true),
                        false: fn (Builder $query): Builder => $query->where(fn (Builder $query) => $query
                            ->whereNull($path)
                            ->orWhere($path, false)),
                    ),
                default => null,
            };

            if ($filter !== null) {
                $filters[] = $filter->label($field->label);
            }
        }

        return $filters;
    }

    /**
     * Infolist sections for viewing a record.
     *
     * @return array<int, Section>
     */
    public static function infolist(string $entity): array
    {
        $sections = [];

        foreach (static::grouped($entity) as $section => $fields) {
            $entries = array_map(function (CustomField $field) {
                $name = 'custom.'.$field->key;

                $entry = match ($field->type) {
                    CustomFieldType::Boolean => IconEntry::make($name)->boolean(),
                    CustomFieldType::Date => TextEntry::make($name)->date(),
                    CustomFieldType::Select, CustomFieldType::Multiselect => TextEntry::make($name)
                        ->badge()
                        ->formatStateUsing(fn (mixed $state): string => $field->optionMap()[(string) $state] ?? (string) $state),
                    CustomFieldType::Url => TextEntry::make($name)->url(fn (mixed $state): ?string => is_string($state) ? $state : null, shouldOpenInNewTab: true),
                    CustomFieldType::Percentage => TextEntry::make($name)->suffix('%'),
                    default => TextEntry::make($name),
                };

                return $entry->label($field->label)->placeholder('—');
            }, $fields);

            $sections[] = Section::make($section)->schema($entries)->columns(2);
        }

        return $sections;
    }

    /**
     * Definitions grouped by section label, in display order.
     *
     * @return array<string, list<CustomField>>
     */
    protected static function grouped(string $entity): array
    {
        $groups = [];

        foreach (app(CustomFieldRegistry::class)->for($entity) as $field) {
            $section = filled($field->section) ? (string) $field->section : __('lite-crm::custom-fields.default_section');
            $groups[$section][] = $field;
        }

        return $groups;
    }
}
