<?php

declare(strict_types=1);

namespace LiteCrm\ImportExport;

use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use LiteCrm\ImportExport\Concerns\HandlesCrmColumns;
use LiteCrm\ImportExport\Concerns\ReportsCompletion;
use LiteCrm\Models\Organisation;

class OrganisationExporter extends Exporter
{
    use HandlesCrmColumns;
    use ReportsCompletion;

    protected static ?string $model = Organisation::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('name')->label(__('lite-crm::organisations.fields.name')),
            ExportColumn::make('type.label')->label(__('lite-crm::organisations.fields.type')),
            ExportColumn::make('status.label')->label(__('lite-crm::organisations.fields.status')),
            ExportColumn::make('territory.label')->label(__('lite-crm::organisations.fields.territory')),
            ExportColumn::make('country')->label(__('lite-crm::organisations.fields.country')),
            ExportColumn::make('region')->label(__('lite-crm::organisations.fields.region')),
            ExportColumn::make('city')->label(__('lite-crm::organisations.fields.city')),
            ExportColumn::make('address')->label(__('lite-crm::organisations.fields.address')),
            ExportColumn::make('website')->label(__('lite-crm::organisations.fields.website')),
            ExportColumn::make('phone')->label(__('lite-crm::organisations.fields.phone')),
            ExportColumn::make('email')->label(__('lite-crm::organisations.fields.email')),
            ExportColumn::make('source')->label(__('lite-crm::organisations.fields.source')),
            ExportColumn::make('owner.name')->label(__('lite-crm::common.fields.owner')),
            ...static::customExportColumns('organisation'),
        ];
    }
}
