<?php

declare(strict_types=1);

namespace LiteCrm\Support;

use Collator;
use ResourceBundle;

/**
 * ISO 3166-1 alpha-2 countries with English names, from the ICU data shipped
 * with PHP's intl extension (required by Filament). Used by the "country"
 * field type of the enquiry form, which stores the two-letter code.
 */
class Countries
{
    /** ICU region codes that are not ISO 3166-1 countries. */
    protected const NOT_COUNTRIES = ['AC', 'CP', 'CQ', 'DG', 'EA', 'EU', 'EZ', 'IC', 'QO', 'TA', 'UN', 'XA', 'XB', 'ZZ'];

    /** @var array<string, string>|null */
    protected static ?array $cache = null;

    /**
     * @return array<string, string> code => name, sorted by name
     */
    public static function all(): array
    {
        if (static::$cache !== null) {
            return static::$cache;
        }

        $countries = [];
        $bundle = ResourceBundle::create('en', 'ICUDATA-region');
        $names = $bundle?->get('Countries');

        if ($names instanceof ResourceBundle) {
            foreach ($names as $code => $name) {
                if (is_string($code) && preg_match('/^[A-Z]{2}$/', $code) === 1 && ! in_array($code, self::NOT_COUNTRIES, true) && is_string($name)) {
                    $countries[$code] = $name;
                }
            }
        }

        $collator = new Collator('en');
        uasort($countries, fn (string $a, string $b): int => (int) $collator->compare($a, $b));

        return static::$cache = $countries;
    }

    /**
     * @return list<string>
     */
    public static function codes(): array
    {
        return array_keys(static::all());
    }

    public static function name(?string $code): ?string
    {
        return $code === null ? null : (static::all()[strtoupper($code)] ?? null);
    }
}
