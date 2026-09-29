<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Resources\Trials\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use LiteCrm\Filament\Resources\Trials\TrialResource;

class ListTrials extends ListRecords
{
    protected static string $resource = TrialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
