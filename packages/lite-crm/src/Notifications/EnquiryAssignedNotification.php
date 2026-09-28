<?php

declare(strict_types=1);

namespace LiteCrm\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use LiteCrm\Filament\Resources\Enquiries\EnquiryResource;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Enquiry;
use Throwable;

class EnquiryAssignedNotification extends Notification implements ShouldQueue
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
        $mail = (new MailMessage)
            ->subject(__('lite-crm::enquiries.mail.assigned.subject', ['from' => $this->enquiry->displayName()]))
            ->line(__('lite-crm::enquiries.mail.assigned.intro', ['from' => $this->enquiry->displayName()]));

        try {
            $mail->action(__('lite-crm::enquiries.mail.new.action'), EnquiryResource::getUrl('view', ['record' => $this->enquiry], panel: LiteCrm::panelId()));
        } catch (Throwable) {
            // No panel routes available: send without a link.
        }

        return $mail;
    }
}
