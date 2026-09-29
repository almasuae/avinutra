<?php

declare(strict_types=1);

namespace LiteCrm\ImportExport;

use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use LiteCrm\ImportExport\Concerns\HandlesCrmColumns;
use LiteCrm\ImportExport\Concerns\ReportsCompletion;
use LiteCrm\Models\Opportunity;

class OpportunityExporter extends Exporter
{
    use HandlesCrmColumns;
    use ReportsCompletion;

    protected static ?string $model = Opportunity::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('name')->label(__('lite-crm::opportunities.fields.name')),
            ExportColumn::make('pipeline.name')->label(__('lite-crm::opportunities.fields.pipeline')),
            ExportColumn::make('stage.name')->label(__('lite-crm::opportunities.fields.stage')),
            ExportColumn::make('organisation.name')->label(__('lite-crm::organisations.label')),
            ExportColumn::make('product.name')->label(__('lite-crm::products.label')),
            ExportColumn::make('volume')->label(__('lite-crm::opportunities.fields.volume')),
            ExportColumn::make('unit')->label(__('lite-crm::opportunities.fields.unit')),
            ExportColumn::make('value')->label(__('lite-crm::opportunities.fields.value')),
            ExportColumn::make('currency')->label(__('lite-crm::common.fields.currency')),
            ExportColumn::make('probability')->label(__('lite-crm::opportunities.fields.probability')),
            ExportColumn::make('expected_close_date')->label(__('lite-crm::opportunities.fields.expected_close_date')),
            ExportColumn::make('lostReason.label')->label(__('lite-crm::opportunities.fields.lost_reason')),
            ExportColumn::make('owner.name')->label(__('lite-crm::common.fields.owner')),
            ...static::customExportColumns('opportunity'),
        ];
    }
}
