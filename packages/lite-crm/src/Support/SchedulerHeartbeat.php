<?php

declare(strict_types=1);

namespace LiteCrm\Support;

/**
 * Records that the scheduler ran, so lite-crm:doctor can tell whether cron works.
 * Written without an audit entry: it changes every minute.
 */
class SchedulerHeartbeat
{
    public function __invoke(): void
    {
        activity()->withoutLogs(function (): void {
            app(CrmSettings::class)->set(CrmSettings::SCHEDULER_HEARTBEAT, now()->toIso8601String());
        });
    }
}
