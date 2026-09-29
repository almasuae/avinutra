<?php

declare(strict_types=1);

namespace App\Filament\Resources\TeamProfiles\Pages;

use App\Filament\Resources\TeamProfiles\TeamProfileResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTeamProfiles extends ListRecords
{
    protected static string $resource = TeamProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
