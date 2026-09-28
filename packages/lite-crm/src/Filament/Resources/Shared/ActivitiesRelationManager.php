<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Resources\Shared;

use Filament\Actions\CreateAction;
use LiteCrm\Filament\Resources\Activities\ActivityResource;

class ActivitiesRelationManager extends ChildRecordsRelationManager
{
    protected static string $relationship = 'activities';

    protected static function childResource(): string
    {
        return ActivityResource::class;
    }

    protected function createAction(): CreateAction
    {
        return CreateAction::make()->label(__('lite-crm::activities.actions.log'));
    }
}
