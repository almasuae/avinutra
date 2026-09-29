<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Resources\Products\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use LiteCrm\Filament\Resources\Products\ProductResource;
use LiteCrm\ImportExport\CrmExporters;
use LiteCrm\ImportExport\ProductExporter;
use LiteCrm\ImportExport\ProductImporter;
use LiteCrm\Models\Product;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CrmExporters::importAction(ProductImporter::class, Product::class),
            CrmExporters::exportAction(ProductExporter::class, 'products'),
            CreateAction::make(),
        ];
    }
}
