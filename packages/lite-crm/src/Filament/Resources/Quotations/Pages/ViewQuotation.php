<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Resources\Quotations\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use LiteCrm\Filament\Actions\LogActivityAction;
use LiteCrm\Filament\Resources\Quotations\QuotationResource;

class ViewQuotation extends ViewRecord
{
    protected static string $resource = QuotationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LogActivityAction::make(),
            EditAction::make(),
        ];
    }
}
