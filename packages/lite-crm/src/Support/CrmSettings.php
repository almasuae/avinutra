<?php

declare(strict_types=1);

namespace LiteCrm\Support;

use Illuminate\Support\Facades\Schema;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Setting;
use Throwable;

/**
 * CRM-wide settings stored in crm_settings and edited in CRM › Settings.
 */
class CrmSettings
{
    public const MFA_REQUIRED_FOR_ALL = 'mfa_required_for_all';

    public const ROLE_LABELS = 'role_labels';

    public const CURRENCIES = 'currencies';

    public const BASE_CURRENCY = 'base_currency';

    public const QUOTATION_PREFIX = 'quotation_prefix';

    public const SCHEDULER_HEARTBEAT = 'scheduler_heartbeat';

    /** @var array<string, mixed>|null */
    protected ?array $values = null;

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        /** @var class-string<Setting> $model */
        $model = LiteCrm::model(Setting::class);

        $model::query()->updateOrCreate(['key' => $key], ['value' => $value]);

        $this->values = null;
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        if ($this->values !== null) {
            return $this->values;
        }

        /** @var class-string<Setting> $model */
        $model = LiteCrm::model(Setting::class);

        try {
            /** @var array<string, mixed> $values */
            $values = $model::query()->pluck('value', 'key')->all();
        } catch (Throwable) {
            // Before the migrations have run (e.g. during installation).
            return [];
        }

        return $this->values = $values;
    }

    public function isMfaRequiredForAll(): bool
    {
        return (bool) $this->get(self::MFA_REQUIRED_FOR_ALL, false);
    }

    public function roleLabel(string $role): string
    {
        /** @var array<string, string> $labels */
        $labels = (array) $this->get(self::ROLE_LABELS, []);

        if (filled($labels[$role] ?? null)) {
            return $labels[$role];
        }

        $key = "lite-crm::roles.names.{$role}";
        $label = __($key);

        return $label === $key ? str($role)->replace('_', ' ')->ucfirst()->toString() : $label;
    }

    public function flush(): void
    {
        $this->values = null;
    }

    public static function tableExists(): bool
    {
        try {
            return Schema::hasTable(LiteCrm::table('settings'));
        } catch (Throwable) {
            return false;
        }
    }
}
