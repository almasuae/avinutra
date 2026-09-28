<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Resources\Shared;

use LiteCrm\Filament\Resources\Tasks\TaskResource;

class TasksRelationManager extends ChildRecordsRelationManager
{
    protected static string $relationship = 'tasks';

    protected static function childResource(): string
    {
        return TaskResource::class;
    }
}
