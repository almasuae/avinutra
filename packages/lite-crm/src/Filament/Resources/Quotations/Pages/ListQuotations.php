<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Resources\Quotations\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use LiteCrm\Filament\Resources\Quotations\QuotationResource;

class ListQuotations extends ListRecords
{
    protected static string $resource = QuotationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
