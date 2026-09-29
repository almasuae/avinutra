<?php

declare(strict_types=1);

namespace LiteCrm\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Throwable;

/**
 * The morning summary: my tasks due, my open enquiries, my next steps, and
 * (for those notified of enquiries) how many new enquiries are waiting.
 */
class DailyDigest extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array{tasks: list<string>, enquiries: list<string>, next_steps: list<string>, new_enquiries: int}  $sections
     */
    public function __construct(public array $sections) {}

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
            ->subject(__('lite-crm::digest.daily.subject', ['app' => config('app.name')]))
            ->greeting(__('lite-crm::digest.daily.greeting'));

        if ($this->sections['new_enquiries'] > 0) {
            $mail->line(__('lite-crm::digest.daily.new_enquiries', ['count' => $this->sections['new_enquiries']]));
        }

        foreach (['tasks', 'enquiries', 'next_steps'] as $section) {
            if ($this->sections[$section] !== []) {
                $mail->line('**'.__("lite-crm::digest.daily.{$section}").'**');

                foreach ($this->sections[$section] as $line) {
                    $mail->line('• '.$line);
                }
            }
        }

        try {
            $mail->action(__('lite-crm::digest.open_crm'), url((string) config('lite-crm.path', 'crm')));
        } catch (Throwable) {
            // Without a URL generator the digest is still useful.
        }

        return $mail->line(__('lite-crm::digest.opt_out'));
    }
}
