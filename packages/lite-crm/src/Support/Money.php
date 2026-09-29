<?php

declare(strict_types=1);

namespace LiteCrm\Support;

use DateTimeInterface;
use Illuminate\Support\Facades\Date;
use LiteCrm\LiteCrm;
use LiteCrm\Models\ExchangeRate;

/**
 * Converts amounts to the base currency with the rates Admins maintain
 * (CRM settings › Exchange rates). Uses the latest rate valid on the date.
 */
class Money
{
    /**
     * The amount in the base currency, or null when no rate is known.
     */
    public static function toBase(float|int|string|null $amount, ?string $currency, ?DateTimeInterface $on = null): ?float
    {
        if ($amount === null || $amount === '') {
            return null;
        }

        $base = LiteCrm::baseCurrency();
        $currency = $currency !== null && $currency !== '' ? strtoupper($currency) : $base;

        if ($currency === $base) {
            return round((float) $amount, 2);
        }

        $rate = static::rate($currency, $on);

        return $rate === null ? null : round((float) $amount * $rate, 2);
    }

    public static function rate(string $currency, ?DateTimeInterface $on = null): ?float
    {
        $rate = LiteCrm::model(ExchangeRate::class)::query()
            ->where('currency', strtoupper($currency))
            ->whereDate('valid_from', '<=', Date::instance($on ?? Date::now())->toDateString())
            ->orderByDesc('valid_from')
            ->value('rate');

        return $rate === null ? null : (float) $rate;
    }

    public static function format(?float $amount, ?string $currency = null): string
    {
        return $amount === null ? '—' : ($currency ?? LiteCrm::baseCurrency()).' '.number_format($amount, 0);
    }
}
