<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Resources\Products\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use LiteCrm\Filament\Actions\LogActivityAction;
use LiteCrm\Filament\Resources\Products\ProductResource;

class ViewProduct extends ViewRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LogActivityAction::make(),
            EditAction::make(),
        ];
    }
}
