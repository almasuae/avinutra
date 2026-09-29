<?php

declare(strict_types=1);

namespace App\Filament\Resources\CalculatorDefaults\Pages;

use App\Filament\Resources\CalculatorDefaults\CalculatorDefaultResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCalculatorDefaults extends ManageRecords
{
    protected static string $resource = CalculatorDefaultResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
