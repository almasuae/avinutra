<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Resources\Quotations\Pages;

use Filament\Resources\Pages\CreateRecord;
use LiteCrm\Filament\Resources\Quotations\QuotationResource;

class CreateQuotation extends CreateRecord
{
    protected static string $resource = QuotationResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
