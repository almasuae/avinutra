<?php

declare(strict_types=1);

namespace LiteCrm\Listeners;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Notification;
use LiteCrm\Enquiries\Submission;
use LiteCrm\Events\EnquiryCaptured;
use LiteCrm\LiteCrm;
use LiteCrm\Notifications\EnquiryAcknowledgement;
use LiteCrm\Notifications\NewEnquiryNotification;

/**
 * E-mails a new enquiry to the configured roles and to its type's mailbox,
 * and acknowledges it to the sender.
 */
class NotifyNewEnquiry
{
    public function handle(EnquiryCaptured $event): void
    {
        $enquiry = $event->enquiry;
        $notification = new NewEnquiryNotification($enquiry);

        /** @var list<string> $roles */
        $roles = config('lite-crm.enquiries.notify_roles', []);

        if ($roles !== []) {
            $recipients = LiteCrm::userModel()::query()
                ->whereHas('roles', fn (Builder $query) => $query->whereIn('name', $roles))
                ->whereHas('crmProfile', fn (Builder $query) => $query->where('is_active', true))
                ->get();

            Notification::send($recipients, $notification);
        }

        $mailbox = $enquiry->type?->meta['mailbox'] ?? null;

        if (is_string($mailbox) && filter_var($mailbox, FILTER_VALIDATE_EMAIL)) {
            Notification::route('mail', $mailbox)->notify($notification);
        }

        if (config('lite-crm.enquiries.acknowledge', true)
            && $enquiry->channel !== Submission::MANUAL
            && filled($enquiry->email)) {
            Notification::route('mail', $enquiry->email)->notify(new EnquiryAcknowledgement($enquiry));
        }
    }
}
