<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Resources\Decisions\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use LiteCrm\Filament\Resources\Decisions\DecisionResource;

class ManageDecisions extends ManageRecords
{
    protected static string $resource = DecisionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
