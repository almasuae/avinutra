<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Resources\ExchangeRates\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use LiteCrm\Filament\Resources\ExchangeRates\ExchangeRateResource;

class ManageExchangeRates extends ManageRecords
{
    protected static string $resource = ExchangeRateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
