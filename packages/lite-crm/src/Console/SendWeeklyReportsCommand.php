<?php

declare(strict_types=1);

namespace LiteCrm\Console;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Date;
use LiteCrm\Events\DocumentExpiring;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Document;
use LiteCrm\Models\Opportunity;
use LiteCrm\Notifications\WeeklyReport;
use LiteCrm\Reports\DigestBuilder;

/**
 * Runs weekly: tells each owner about their documents that expire soon
 * (firing DocumentExpiring for each) and their opportunities gone quiet.
 */
class SendWeeklyReportsCommand extends Command
{
    protected $signature = 'lite-crm:send-weekly-reports';

    protected $description = 'Send each owner their expiring documents and stale opportunities';

    public function handle(DigestBuilder $builder): int
    {
        $sent = 0;
        /** @var array<int, true> $announced */
        $announced = [];

        $users = LiteCrm::userModel()::query()
            ->whereHas('crmProfile', fn (Builder $query) => $query->where('is_active', true))
            ->get();

        foreach ($users as $user) {
            if (! $user->canAccessCrm()) {
                continue;
            }

            $report = $builder->weekly($user);
            $timezone = $user->crmTimezone();

            // Once per document, however many users can see it.
            foreach ($report['documents'] as $document) {
                if (isset($announced[$document->id])) {
                    continue;
                }

                $announced[$document->id] = true;
                DocumentExpiring::dispatch($document, (int) Date::today()->diffInDays($document->expires_on, true));
            }

            if (DigestBuilder::isEmpty($report)) {
                continue;
            }

            $user->notify(new WeeklyReport(
                documents: array_map(fn (Document $document): string => $document->title.' — '.$document->expires_on?->setTimezone($timezone)->format('j M Y'), $report['documents']),
                stale: array_map(fn (Opportunity $opportunity): string => $opportunity->name.' — '.$opportunity->updated_at?->setTimezone($timezone)->format('j M Y'), $report['stale']),
            ));
            $sent++;
        }

        $this->components->info(__('lite-crm::digest.sent', ['count' => $sent]));

        return self::SUCCESS;
    }
}
