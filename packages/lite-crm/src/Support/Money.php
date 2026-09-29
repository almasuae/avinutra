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

    /**
     * Currencies whose rate in use is older than lite-crm.exchange_rates.stale_after_days
     * (rates are entered by hand). The base currency and currencies without a rate are
     * not listed.
     *
     * @param  iterable<string|null>  $currencies
     * @return array<string, string> currency => date the rate became valid (Y-m-d)
     */
    public static function staleRates(iterable $currencies, ?DateTimeInterface $on = null): array
    {
        $on = Date::instance($on ?? Date::now());
        $cutoff = $on->copy()->subDays((int) config('lite-crm.exchange_rates.stale_after_days', 30))->toDateString();
        $base = LiteCrm::baseCurrency();
        $stale = [];

        foreach ($currencies as $currency) {
            $currency = strtoupper((string) $currency);

            if ($currency === '' || $currency === $base || array_key_exists($currency, $stale)) {
                continue;
            }

            $validFrom = LiteCrm::model(ExchangeRate::class)::query()
                ->where('currency', $currency)
                ->whereDate('valid_from', '<=', $on->toDateString())
                ->orderByDesc('valid_from')
                ->value('valid_from');

            if ($validFrom !== null && Date::parse($validFrom)->toDateString() < $cutoff) {
                $stale[$currency] = Date::parse($validFrom)->toDateString();
            }
        }

        ksort($stale);

        return $stale;
    }

    /**
     * @param  array<string, string>  $stale  from staleRates()
     */
    public static function describeStaleRates(array $stale): string
    {
        return implode(', ', array_map(
            fn (string $currency, string $date): string => $currency.' ('.Date::parse($date)->format('j M Y').')',
            array_keys($stale),
            $stale,
        ));
    }

    public static function format(?float $amount, ?string $currency = null): string
    {
        return $amount === null ? '—' : ($currency ?? LiteCrm::baseCurrency()).' '.number_format($amount, 0);
    }
}
