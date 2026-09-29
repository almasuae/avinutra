<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Resources\Products\Pages;

use Filament\Resources\Pages\CreateRecord;
use LiteCrm\Filament\Resources\Products\ProductResource;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
