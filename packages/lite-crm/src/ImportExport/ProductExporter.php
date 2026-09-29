<?php

declare(strict_types=1);

namespace LiteCrm\ImportExport;

use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Support\Contracts\HasLabel;
use LiteCrm\ImportExport\Concerns\HandlesCrmColumns;
use LiteCrm\ImportExport\Concerns\ReportsCompletion;
use LiteCrm\Models\Product;

class ProductExporter extends Exporter
{
    use HandlesCrmColumns;
    use ReportsCompletion;

    protected static ?string $model = Product::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('name')->label(__('lite-crm::products.fields.name')),
            ExportColumn::make('category.label')->label(__('lite-crm::products.fields.category')),
            ExportColumn::make('availability')->label(__('lite-crm::products.fields.availability'))
                ->formatStateUsing(fn (mixed $state): string => $state instanceof HasLabel ? (string) $state->getLabel() : (string) $state),
            ExportColumn::make('publish_on_website')->label(__('lite-crm::products.fields.published'))
                ->formatStateUsing(fn (mixed $state): string => $state ? __('lite-crm::import.yes') : __('lite-crm::import.no')),
            ExportColumn::make('description')->label(__('lite-crm::products.fields.description')),
            ExportColumn::make('packaging')->label(__('lite-crm::products.fields.packaging')),
            ExportColumn::make('storage')->label(__('lite-crm::products.fields.storage')),
            ExportColumn::make('shelf_life')->label(__('lite-crm::products.fields.shelf_life')),
            ...static::customExportColumns('product'),
        ];
    }
}
