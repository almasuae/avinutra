<?php

declare(strict_types=1);

namespace LiteCrm\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Weekly, to each owner: documents about to expire and opportunities gone quiet.
 */
class WeeklyReport extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  list<string>  $documents
     * @param  list<string>  $stale
     * @param  string  $staleRates  e.g. "EUR (1 Aug 2026)", empty when all rates are recent
     */
    public function __construct(public array $documents, public array $stale, public string $staleRates = '') {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)->subject(__('lite-crm::digest.weekly.subject', ['app' => config('app.name')]));

        if ($this->documents !== []) {
            $mail->line('**'.__('lite-crm::digest.weekly.documents', ['days' => (int) config('lite-crm.notifications.expiry_warning_days', 60)]).'**');

            foreach ($this->documents as $line) {
                $mail->line('• '.$line);
            }
        }

        if ($this->stale !== []) {
            $mail->line('**'.__('lite-crm::digest.weekly.stale', ['days' => (int) config('lite-crm.notifications.stale_opportunity_days', 21)]).'**');

            foreach ($this->stale as $line) {
                $mail->line('• '.$line);
            }
        }

        if ($this->staleRates !== '') {
            $mail->line(__('lite-crm::digest.weekly.stale_rates', ['days' => (int) config('lite-crm.exchange_rates.stale_after_days', 30), 'rates' => $this->staleRates]));
        }

        return $mail->action(__('lite-crm::digest.open_crm'), url((string) config('lite-crm.path', 'crm')));
    }
}
