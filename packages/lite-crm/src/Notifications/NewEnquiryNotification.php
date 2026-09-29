<?php

declare(strict_types=1);

namespace LiteCrm\Notifications;

use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;
use LiteCrm\Filament\Resources\Enquiries\EnquiryResource;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Enquiry;
use LiteCrm\Notifications\Concerns\InAppAndMail;
use Throwable;

/**
 * Tells the team (and the mailbox of the enquiry type) that an enquiry arrived.
 */
class NewEnquiryNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Enquiry $enquiry) {}

    use InAppAndMail;

    protected function toInApp(): FilamentNotification
    {
        $notification = FilamentNotification::make()->title(__('lite-crm::enquiries.mail.new.subject', ['type' => $this->enquiry->type->label ?? __('lite-crm::enquiries.label'), 'from' => $this->enquiry->displayName()]))->info();

        try {
            $notification->actions([
                Action::make('open')->label(__('lite-crm::common.open'))->url(EnquiryResource::getUrl('view', ['record' => $this->enquiry], panel: LiteCrm::panelId()))->markAsRead(),
            ]);
        } catch (Throwable) {
            // No panel routes available: no link.
        }

        return $notification;
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
