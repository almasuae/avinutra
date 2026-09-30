<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// No Supervisor on the server: the scheduler (cron, every minute) drains the queue.
// The overlap lock expires after 10 minutes (not the default 24 hours), so a worker that
// crashes (e.g. pcntl disabled for CLI PHP) cannot block the queue for a day.
Schedule::command('queue:work --stop-when-empty --max-time=50')->everyMinute()->withoutOverlapping(10);

// Backups (spatie/laravel-backup, v5 §F3): nightly database + uploaded files to the
// server's own "backups" disk; weekly clean-up of old backups; a daily health check
// that e-mails BACKUP_NOTIFICATION_EMAIL if the newest backup is missing or too old.
Schedule::command('backup:run')->dailyAt('02:00')->withoutOverlapping();
Schedule::command('backup:clean')->weeklyOn(0, '03:00')->withoutOverlapping();
Schedule::command('backup:monitor')->dailyAt('08:00');
