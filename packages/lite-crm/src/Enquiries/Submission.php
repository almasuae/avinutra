<?php

declare(strict_types=1);

namespace LiteCrm\Enquiries;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

/**
 * How an enquiry arrived: who sent it (IP, browser), the spam signals, the
 * consent, and any uploaded files.
 */
final class Submission
{
    public const FORM = 'form';

    public const API = 'api';

    public const MANUAL = 'manual';

    /**
     * @param  list<UploadedFile>  $attachments
     */
    public function __construct(
        public readonly string $channel = self::MANUAL,
        public readonly ?string $ip = null,
        public readonly ?string $userAgent = null,
        public readonly ?string $honeypot = null,
        public readonly ?int $startedAt = null,
        public readonly bool $consent = false,
        public readonly array $attachments = [],
    ) {}

    /**
     * A submission from code or from a signed-in user: no spam checks apply.
     */
    public static function trusted(bool $consent = false): self
    {
        return new self(channel: self::MANUAL, consent: $consent);
    }

    /**
     * @param  list<UploadedFile>  $attachments
     */
    public static function fromRequest(Request $request, string $channel, ?string $honeypot, ?int $startedAt, bool $consent, array $attachments = []): self
    {
        return new self(
            channel: $channel,
            ip: $request->ip(),
            userAgent: $request->userAgent(),
            honeypot: $honeypot,
            startedAt: $startedAt,
            consent: $consent,
            attachments: $attachments,
        );
    }

    public function isTrusted(): bool
    {
        return $this->channel === self::MANUAL;
    }
}
