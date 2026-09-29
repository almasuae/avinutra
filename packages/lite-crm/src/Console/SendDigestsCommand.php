<?php

declare(strict_types=1);

namespace LiteCrm\Console;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Date;
use LiteCrm\LiteCrm;
use LiteCrm\Models\UserProfile;
use LiteCrm\Notifications\DailyDigest;
use LiteCrm\Reports\DigestBuilder;

/**
 * Runs every hour: sends the daily digest to each user whose local time has
 * just reached the digest hour (lite-crm.notifications.digest_hour), once per
 * local day, unless they opted out or have nothing to report.
 */
class SendDigestsCommand extends Command
{
    protected $signature = 'lite-crm:send-digests';

    protected $description = 'Send the daily CRM digest to users whose local time is the digest hour';

    public function handle(DigestBuilder $builder): int
    {
        $hour = (int) config('lite-crm.notifications.digest_hour', 8);
        $sent = 0;

        $users = LiteCrm::userModel()::query()
            ->whereHas('crmProfile', fn (Builder $query) => $query->where('is_active', true)->where('receives_digest', true))
            ->with('crmProfile')
            ->get();

        foreach ($users as $user) {
            if (! $user->canAccessCrm()) {
                continue;
            }

            $local = Date::now()->setTimezone($user->crmTimezone());
            /** @var UserProfile $profile */
            $profile = $user->getCrmProfile();

            if ($local->hour !== $hour || $profile->last_digest_on?->toDateString() === $local->toDateString()) {
                continue;
            }

            $sections = $builder->daily($user);
            $profile->forceFill(['last_digest_on' => $local->toDateString()])->saveQuietly();

            if (DigestBuilder::isEmpty($sections)) {
                continue;
            }

            $user->notify(new DailyDigest($sections));
            $sent++;
        }

        $this->components->info(__('lite-crm::digest.sent', ['count' => $sent]));

        return self::SUCCESS;
    }
}
