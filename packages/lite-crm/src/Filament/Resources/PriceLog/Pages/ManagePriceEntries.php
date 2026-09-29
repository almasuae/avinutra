<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Resources\PriceLog\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use LiteCrm\Filament\FormLayout;
use LiteCrm\Filament\Resources\PriceLog\PriceEntryResource;
use LiteCrm\ImportExport\CrmExporters;
use LiteCrm\ImportExport\PriceEntryExporter;
use LiteCrm\ImportExport\PriceEntryImporter;
use LiteCrm\Models\PriceEntry;

class ManagePriceEntries extends ManageRecords
{
    protected static string $resource = PriceEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CrmExporters::importAction(PriceEntryImporter::class, PriceEntry::class),
            CrmExporters::exportAction(PriceEntryExporter::class, 'price_log'),
            FormLayout::wide(CreateAction::make()),
        ];
    }
}
