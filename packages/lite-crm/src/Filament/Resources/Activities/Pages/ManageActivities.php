<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Resources\Activities\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use LiteCrm\Filament\Resources\Activities\ActivityResource;

class ManageActivities extends ManageRecords
{
    protected static string $resource = ActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label(__('lite-crm::activities.actions.log')),
        ];
    }
}
