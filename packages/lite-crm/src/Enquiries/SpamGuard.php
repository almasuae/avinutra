<?php

declare(strict_types=1);

namespace LiteCrm\Enquiries;

use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Spam protection without external services.
 *
 * - honeypot: a field people never see; bots fill it in;
 * - minimum fill time: people take more than a few seconds to fill a form;
 * - per-IP limit: at most N submissions per window.
 */
class SpamGuard
{
    public const HONEYPOT = 'honeypot';

    public const TOO_FAST = 'too_fast';

    public const INVALID_TIMING = 'invalid_timing';

    /**
     * Why a submission looks like spam, or null when it looks genuine.
     */
    public function reason(Submission $submission): ?string
    {
        if ($submission->isTrusted()) {
            return null;
        }

        if (filled($submission->honeypot)) {
            return self::HONEYPOT;
        }

        if ($submission->startedAt !== null) {
            $elapsed = Date::now()->getTimestamp() - $submission->startedAt;

            if ($elapsed < 0) {
                return self::INVALID_TIMING;
            }

            if ($elapsed < (int) config('lite-crm.enquiries.min_fill_seconds', 3)) {
                return self::TOO_FAST;
            }
        }

        return null;
    }

    /**
     * Counts a submission from this IP; true when the IP is over its limit.
     */
    public function tooManyAttempts(?string $ip): bool
    {
        $key = 'lite-crm-enquiry:'.hash('sha256', (string) $ip);
        $max = (int) config('lite-crm.enquiries.rate_limit.attempts', 5);

        if (RateLimiter::tooManyAttempts($key, $max)) {
            return true;
        }

        RateLimiter::hit($key, (int) config('lite-crm.enquiries.rate_limit.decay_seconds', 600));

        return false;
    }
}
