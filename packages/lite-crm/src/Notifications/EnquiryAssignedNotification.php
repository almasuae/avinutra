<?php

declare(strict_types=1);

namespace LiteCrm\Notifications;

use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use LiteCrm\Filament\Resources\Enquiries\EnquiryResource;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Enquiry;
use LiteCrm\Notifications\Concerns\InAppAndMail;
use Throwable;

class EnquiryAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Enquiry $enquiry) {}

    use InAppAndMail;

    protected function toInApp(): FilamentNotification
    {
        $notification = FilamentNotification::make()->title(__('lite-crm::enquiries.mail.assigned.subject', ['from' => $this->enquiry->displayName()]))->info();

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
