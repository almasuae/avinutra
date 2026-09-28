<?php

declare(strict_types=1);

namespace LiteCrm\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use LiteCrm\Filament\Resources\Tasks\TaskResource;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Task;
use Throwable;

class TaskAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Task $task) {}

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
            ->subject(__('lite-crm::tasks.mail.subject', ['title' => $this->task->title]))
            ->line(__('lite-crm::tasks.mail.intro', ['title' => $this->task->title]));

        if ($this->task->due_at !== null) {
            $timezone = method_exists($notifiable, 'crmTimezone') ? $notifiable->crmTimezone() : (string) config('app.timezone');

            $mail->line(__('lite-crm::tasks.mail.due', [
                'date' => $this->task->due_at->copy()->setTimezone($timezone)->format('j M Y, H:i'),
            ]));
        }

        try {
            $mail->action(__('lite-crm::tasks.mail.action'), TaskResource::getUrl(panel: LiteCrm::panelId()));
        } catch (Throwable) {
            // No panel routes (e.g. in a console-only context): send without a link.
        }

        return $mail;
    }
}
