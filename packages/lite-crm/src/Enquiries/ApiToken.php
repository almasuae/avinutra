<?php

declare(strict_types=1);

namespace LiteCrm\Enquiries;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use LiteCrm\Support\CrmSettings;

/**
 * The token that other websites send to the enquiry endpoint. Only a SHA-256
 * hash is stored; the plain token is shown once, when it is generated.
 */
class ApiToken
{
    public const HASH = 'enquiry_api_token_hash';

    public const CREATED_AT = 'enquiry_api_token_created_at';

    public function __construct(protected CrmSettings $settings) {}

    /**
     * Creates a new token, replacing (and so revoking) any previous one.
     *
     * @return string The plain token, to be shown once.
     */
    public function rotate(): string
    {
        $token = 'lcrm_'.Str::random(48);

        $this->settings->set(self::HASH, hash('sha256', $token));
        $this->settings->set(self::CREATED_AT, Date::now()->toIso8601String());

        return $token;
    }

    public function revoke(): void
    {
        $this->settings->set(self::HASH, null);
        $this->settings->set(self::CREATED_AT, null);
    }

    public function exists(): bool
    {
        return filled($this->settings->get(self::HASH));
    }

    public function createdAt(): ?Carbon
    {
        $value = $this->settings->get(self::CREATED_AT);

        return is_string($value) ? Date::parse($value) : null;
    }

    public function verify(?string $token): bool
    {
        $hash = $this->settings->get(self::HASH);

        return is_string($hash) && $hash !== '' && is_string($token) && $token !== ''
            && hash_equals($hash, hash('sha256', $token));
    }
}
