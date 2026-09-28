<?php

declare(strict_types=1);

namespace LiteCrm\Listeners;

use Illuminate\Support\Facades\Auth;
use LiteCrm\Contracts\CrmUser;
use LiteCrm\Events\TaskAssigned;
use LiteCrm\Notifications\TaskAssignedNotification;

/**
 * E-mails the assignee, unless they assigned the task to themselves.
 */
class NotifyTaskAssignee
{
    public function handle(TaskAssigned $event): void
    {
        // Query afresh: a cached relation would still point at the previous assignee.
        $assignee = $event->task->assignee()->first();

        if (! $assignee instanceof CrmUser || $assignee->getKey() === Auth::id()) {
            return;
        }

        $assignee->notify(new TaskAssignedNotification($event->task));
    }
}
