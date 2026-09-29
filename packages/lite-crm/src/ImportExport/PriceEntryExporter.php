<?php

declare(strict_types=1);

namespace LiteCrm\ImportExport;

use BackedEnum;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use LiteCrm\ImportExport\Concerns\ReportsCompletion;
use LiteCrm\Models\PriceEntry;

class PriceEntryExporter extends Exporter
{
    use ReportsCompletion;

    protected static ?string $model = PriceEntry::class;

    public static function getColumns(): array
    {
        $enum = fn (mixed $state): string => $state instanceof BackedEnum ? (string) $state->value : (string) $state;

        return [
            ExportColumn::make('observed_on')->label(__('lite-crm::price-log.fields.observed_on')),
            ExportColumn::make('product.name')->label(__('lite-crm::products.label')),
            ExportColumn::make('source_type')->label(__('lite-crm::price-log.fields.source_type'))->formatStateUsing($enum),
            ExportColumn::make('sourceOrganisation.name')->label(__('lite-crm::price-log.fields.source_organisation')),
            ExportColumn::make('basis')->label(__('lite-crm::price-log.fields.basis'))->formatStateUsing($enum),
            ExportColumn::make('location')->label(__('lite-crm::price-log.fields.location')),
            ExportColumn::make('price')->label(__('lite-crm::price-log.fields.price')),
            ExportColumn::make('currency')->label(__('lite-crm::common.fields.currency')),
            ExportColumn::make('unit')->label(__('lite-crm::price-log.fields.unit')),
            ExportColumn::make('valid_until')->label(__('lite-crm::price-log.fields.valid_until')),
            ExportColumn::make('reference')->label(__('lite-crm::price-log.fields.reference')),
            ExportColumn::make('confidence')->label(__('lite-crm::price-log.fields.confidence'))->formatStateUsing($enum),
        ];
    }
}
