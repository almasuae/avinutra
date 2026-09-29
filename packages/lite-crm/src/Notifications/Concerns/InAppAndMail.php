<?php

declare(strict_types=1);

namespace LiteCrm\Notifications\Concerns;

use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Notifications\AnonymousNotifiable;

/**
 * Team members get the notification in the CRM (the bell) and by e-mail;
 * plain addresses (mailboxes, senders) by e-mail only.
 */
trait InAppAndMail
{
    /**
     * The in-app version of the notification.
     */
    abstract protected function toInApp(): FilamentNotification;

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return $notifiable instanceof AnonymousNotifiable ? ['mail'] : ['database', 'mail'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return $this->toInApp()->getDatabaseMessage();
    }
}
