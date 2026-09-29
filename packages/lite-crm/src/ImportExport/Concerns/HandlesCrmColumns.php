<?php

declare(strict_types=1);

namespace LiteCrm\ImportExport\Concerns;

use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Imports\ImportColumn;
use Illuminate\Database\Eloquent\Model;
use LiteCrm\CustomFields\CustomFieldRegistry;
use LiteCrm\CustomFields\CustomFieldType;
use LiteCrm\Models\CustomField;
use LiteCrm\Models\Lookup;

/**
 * Columns shared by the CRM importers and exporters: lists (by label or key)
 * and the custom fields defined for an entity.
 */
trait HandlesCrmColumns
{
    /**
     * A column holding a list entry's label (or key), stored as its id.
     */
    protected static function lookupImportColumn(string $name, string $attribute, string $type, string $label): ImportColumn
    {
        return ImportColumn::make($name)
            ->label($label)
            ->rules(['nullable', 'string', 'max:255'])
            ->fillRecordUsing(function (Model $record, mixed $state) use ($attribute, $type): void {
                $record->setAttribute($attribute, static::lookupId($type, $state));
            });
    }

    public static function lookupId(string $type, mixed $value): ?int
    {
        if (blank($value)) {
            return null;
        }

        $value = mb_strtolower(trim((string) $value));

        /** @var int|null $id */
        $id = Lookup::query()
            ->where('type', $type)
            ->where(fn ($query) => $query->whereRaw('LOWER(label) = ?', [$value])->orWhereRaw('LOWER('.$query->getQuery()->getGrammar()->wrap('key').') = ?', [$value]))
            ->value('id');

        return $id;
    }

    /**
     * One import column per active custom field ("custom_{key}").
     *
     * @return list<ImportColumn>
     */
    protected static function customImportColumns(string $entity): array
    {
        return app(CustomFieldRegistry::class)->for($entity)
            ->map(fn (CustomField $field): ImportColumn => ImportColumn::make('custom_'.$field->key)
                ->label($field->label)
                ->fillRecordUsing(function (Model $record, mixed $state) use ($field): void {
                    if (blank($state) || ! method_exists($record, 'setCustomValue')) {
                        return;
                    }

                    // Several choices are separated by commas; validation happens when the record is saved.
                    $record->setCustomValue($field->key, $field->type === CustomFieldType::Multiselect
                        ? array_values(array_filter(array_map('trim', explode(',', (string) $state))))
                        : $state);
                }))
            ->values()
            ->all();
    }

    /**
     * One export column per active custom field.
     *
     * @return list<ExportColumn>
     */
    protected static function customExportColumns(string $entity): array
    {
        return app(CustomFieldRegistry::class)->for($entity)
            ->map(fn (CustomField $field): ExportColumn => ExportColumn::make('custom_'.$field->key)
                ->label($field->label)
                ->state(function (Model $record) use ($field): string {
                    $value = method_exists($record, 'getCustomValue') ? $record->getCustomValue($field->key) : null;

                    if (is_array($value)) {
                        return implode(', ', array_map(fn ($item): string => $field->optionMap()[(string) $item] ?? (string) $item, $value));
                    }

                    if (is_bool($value)) {
                        return $value ? __('lite-crm::import.yes') : __('lite-crm::import.no');
                    }

                    return $field->type === CustomFieldType::Select
                        ? ($field->optionMap()[(string) $value] ?? (string) $value)
                        : (string) $value;
                }))
            ->values()
            ->all();
    }
}
