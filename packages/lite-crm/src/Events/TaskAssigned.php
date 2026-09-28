<?php

declare(strict_types=1);

namespace LiteCrm\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use LiteCrm\Models\Task;

/**
 * A task was created with, or changed to, an assignee.
 */
class TaskAssigned
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public Task $task) {}
}
