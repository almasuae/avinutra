<?php

declare(strict_types=1);

namespace LiteCrm\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserInvitation extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $url,
        public int $expiresInHours,
        public ?string $inviterName = null,
    ) {}

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

        return (new MailMessage)
            ->subject(__('lite-crm::users.invitation.mail.subject', ['app' => $app]))
            ->greeting(__('lite-crm::users.invitation.mail.greeting'))
            ->line($this->inviterName !== null
                ? __('lite-crm::users.invitation.mail.intro_by', ['app' => $app, 'name' => $this->inviterName])
                : __('lite-crm::users.invitation.mail.intro', ['app' => $app]))
            ->action(__('lite-crm::users.invitation.mail.action'), $this->url)
            ->line(__('lite-crm::users.invitation.mail.expiry', ['hours' => $this->expiresInHours]))
            ->line(__('lite-crm::users.invitation.mail.ignore'));
    }
}
