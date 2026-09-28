<?php

declare(strict_types=1);

namespace LiteCrm\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;
use LiteCrm\Filament\Resources\Enquiries\EnquiryResource;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Enquiry;
use Throwable;

/**
 * Tells the team (and the mailbox of the enquiry type) that an enquiry arrived.
 */
class NewEnquiryNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Enquiry $enquiry) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $enquiry = $this->enquiry;
        $type = $enquiry->type->label ?? __('lite-crm::enquiries.label');

        $mail = (new MailMessage)
            ->subject(__('lite-crm::enquiries.mail.new.subject', ['type' => $type, 'from' => $enquiry->displayName()]))
            ->line(__('lite-crm::enquiries.mail.new.intro', ['type' => $type]))
            ->line(__('lite-crm::enquiries.mail.new.from', ['from' => $enquiry->displayName()]));

        if (filled($enquiry->message)) {
            $mail->line('"'.Str::limit((string) $enquiry->message, 500).'"');
        }

        if (filled($enquiry->source_url)) {
            $mail->line(__('lite-crm::enquiries.mail.new.source', ['url' => $enquiry->source_url]));
        }

        try {
            $mail->action(__('lite-crm::enquiries.mail.new.action'), EnquiryResource::getUrl('view', ['record' => $enquiry], panel: LiteCrm::panelId()));
        } catch (Throwable) {
            // No panel routes available: send without a link.
        }

        return $mail;
    }
}
