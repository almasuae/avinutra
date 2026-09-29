<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Resources\PriceLog\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use LiteCrm\Filament\Resources\PriceLog\PriceEntryResource;

class ManagePriceEntries extends ManageRecords
{
    protected static string $resource = PriceEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
