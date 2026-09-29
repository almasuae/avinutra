<?php

declare(strict_types=1);

namespace LiteCrm\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Enquiry;

/**
 * Confirms receipt to the sender. Sent from the site's no-reply address
 * (MAIL_FROM_ADDRESS). Mentions a response time only if one is configured.
 */
class EnquiryAcknowledgement extends Notification implements ShouldQueue
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
        $app = (string) config('app.name');
        $responseTime = LiteCrm::enquiryResponseTime();

        $mail = (new MailMessage)
            ->subject(__('lite-crm::enquiries.mail.acknowledgement.subject', ['app' => $app]))
            ->greeting(filled($this->enquiry->name)
                ? __('lite-crm::enquiries.mail.acknowledgement.greeting_name', ['name' => $this->enquiry->name])
                : __('lite-crm::enquiries.mail.acknowledgement.greeting'))
            ->line(__('lite-crm::enquiries.mail.acknowledgement.received', ['app' => $app]))
            ->line(__('lite-crm::enquiries.mail.acknowledgement.reference', ['reference' => $this->enquiry->id]));

        if ($responseTime !== null) {
            $mail->line(__('lite-crm::enquiries.mail.acknowledgement.response_time', ['time' => $responseTime]));
        }

        return $mail->line(__('lite-crm::enquiries.mail.acknowledgement.no_reply'));
    }
}
