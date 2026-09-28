<?php

declare(strict_types=1);

namespace LiteCrm\Listeners;

use Illuminate\Support\Facades\Auth;
use LiteCrm\Contracts\CrmUser;
use LiteCrm\Events\EnquiryAssigned;
use LiteCrm\Notifications\EnquiryAssignedNotification;

/**
 * E-mails the assignee, unless they assigned the enquiry to themselves.
 */
class NotifyEnquiryAssignee
{
    public function handle(EnquiryAssigned $event): void
    {
        $assignee = $event->enquiry->assignee()->first();

        if (! $assignee instanceof CrmUser || $assignee->getKey() === Auth::id()) {
            return;
        }

        $assignee->notify(new EnquiryAssignedNotification($event->enquiry));
    }
}
