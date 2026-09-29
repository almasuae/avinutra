<?php

declare(strict_types=1);

namespace App\Services\Calculators;

use InvalidArgumentException;

/**
 * Tool 2 — Landed Cost Calculator (universal; no country rates built in).
 *
 * All amounts per shipment. Purchase-currency amounts (price, freight, insurance)
 * are converted at the exchange rate (local units per 1 purchase unit); duties,
 * taxes and local charges are in the local currency.
 *
 *   goods      = price per kg × kg
 *   CIF        = goods + freight + insurance            (freight/insurance only if the basis needs them)
 *   customs    = CIF (default) or FOB, per the valuation basis
 *   duties     = each line on the customs value, or on customs value + previous duties
 *   VAT/GST    = rate × (customs value + duties)       (or customs value only)
 *   other tax  = rate × its base                        (customs value [+ duties [+ VAT]])
 *   charges    = each amount per shipment or per container
 *   bank       = CIF × bank %;  financing = CIF × annual % × days ÷ 365   (v5 §E3)
 *   gross      = CIF + duties + VAT + other taxes + charges + bank + financing
 *   net        = gross − recoverable taxes
 */
class LandedCostCalculator
{
    public const BASES = ['EXW', 'FOB', 'CFR', 'CIF', 'CPT', 'CIP', 'DAP'];

    /** Bases whose price excludes main freight (the buyer pays it). */
    public const NEEDS_FREIGHT = ['EXW', 'FOB'];

    /** Bases whose price excludes insurance. */
    public const NEEDS_INSURANCE = ['EXW', 'FOB', 'CFR', 'CPT'];

    public static function needsFreight(string $basis): bool
    {
        return in_array($basis, self::NEEDS_FREIGHT, true);
    }

    public static function needsInsurance(string $basis): bool
    {
        return in_array($basis, self::NEEDS_INSURANCE, true);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function calculate(array $input): array
    {
        $rate = (float) ($input['exchange_rate'] ?? 0);
        $kgPerContainer = (float) ($input['kg_per_container'] ?? 0);
        $quantity = (float) ($input['quantity'] ?? 0);
        $basis = strtoupper((string) ($input['basis'] ?? 'CIF'));

        if ($rate <= 0) {
            throw new InvalidArgumentException('Enter the exchange rate.');
        }

        if (! in_array($basis, self::BASES, true)) {
            throw new InvalidArgumentException('Choose a price basis.');
        }

        $kg = match ($input['quantity_unit'] ?? 'kg') {
            'mt' => $quantity * 1000,
            'bags' => $quantity * (float) ($input['kg_per_bag'] ?? 0),
            'containers' => $quantity * $kgPerContainer,
            default => $quantity,
        };

        if ($kg <= 0) {
            throw new InvalidArgumentException('Enter the quantity.');
        }

        $containers = ($input['quantity_unit'] ?? 'kg') === 'containers'
            ? $quantity
            : ($kgPerContainer > 0 ? ceil($kg / $kgPerContainer) : 0.0);

        $working = [];
        $pricePerKg = ($input['price_unit'] ?? 'kg') === 'mt' ? (float) ($input['price'] ?? 0) / 1000 : (float) ($input['price'] ?? 0);
        $goods = $pricePerKg * $kg;
        $working[] = sprintf('Goods: %s per kg × %s kg = %s (purchase currency)', self::n($pricePerKg, 4), self::n($kg), self::n($goods));

        $freight = 0.0;
        if (self::needsFreight($basis)) {
            $freight = (float) ($input['freight_amount'] ?? 0) * match ($input['freight_per'] ?? 'shipment') {
                'container' => $containers,
                'kg' => $kg,
                default => 1,
            };
            $working[] = sprintf('Freight (%s price excludes it): %s', $basis, self::n($freight));
        }

        $insurance = 0.0;
        if (self::needsInsurance($basis)) {
            $insurance = ($input['insurance_type'] ?? 'percent') === 'amount'
                ? (float) ($input['insurance_value'] ?? 0)
                : ($goods + $freight) * (float) ($input['insurance_value'] ?? 0) / 100;
            $working[] = ($input['insurance_type'] ?? 'percent') === 'amount'
                ? sprintf('Insurance: fixed %s', self::n($insurance))
                : sprintf('Insurance: (%s + %s) × %s%% = %s', self::n($goods), self::n($freight), self::n((float) ($input['insurance_value'] ?? 0), 3), self::n($insurance));
        }

        $cif = $goods + $freight + $insurance;
        $working[] = sprintf('CIF value = %s + %s + %s = %s (purchase currency)', self::n($goods), self::n($freight), self::n($insurance), self::n($cif));

        $valuation = ($input['valuation'] ?? 'CIF') === 'FOB' ? 'FOB' : 'CIF';
        if ($valuation === 'FOB') {
            $customsPurchase = self::needsFreight($basis)
                ? $goods
                : max(0.0, $goods - (float) ($input['freight_included'] ?? 0) - (float) ($input['insurance_included'] ?? 0));
            $working[] = sprintf('Customs value (FOB basis) = %s (purchase currency)', self::n($customsPurchase));
        } else {
            $customsPurchase = $cif;
            $working[] = 'Customs value (CIF basis) = CIF value';
        }

        $customs = $customsPurchase * $rate;
        $working[] = sprintf('Customs value in local currency = %s × %s = %s', self::n($customsPurchase), self::n($rate, 4), self::n($customs));

        $duties = [];
        $dutyTotal = 0.0;
        foreach ((array) ($input['duties'] ?? []) as $duty) {
            if (! is_array($duty) || (float) ($duty['rate'] ?? 0) === 0.0) {
                continue;
            }
            $dutyRate = (float) $duty['rate'];
            $base = ($duty['base'] ?? 'customs') === 'cumulative' ? $customs + $dutyTotal : $customs;
            $amount = match ($duty['type'] ?? 'percent') {
                'per_kg' => $dutyRate * $kg,
                'per_mt' => $dutyRate * $kg / 1000,
                default => $base * $dutyRate / 100,
            };
            $name = trim((string) ($duty['name'] ?? '')) ?: 'Duty';
            $working[] = match ($duty['type'] ?? 'percent') {
                'per_kg' => sprintf('%s: %s per kg × %s kg = %s', $name, self::n($dutyRate, 4), self::n($kg), self::n($amount)),
                'per_mt' => sprintf('%s: %s per tonne × %s t = %s', $name, self::n($dutyRate, 4), self::n($kg / 1000, 3), self::n($amount)),
                default => sprintf('%s: %s × %s%% = %s', $name, self::n($base), self::n($dutyRate, 3), self::n($amount)),
            };
            $duties[] = ['name' => $name, 'amount' => $amount];
            $dutyTotal += $amount;
        }

        $vatRate = (float) ($input['vat_rate'] ?? 0);
        $vatBase = ($input['vat_base'] ?? 'customs_duties') === 'customs' ? $customs : $customs + $dutyTotal;
        $vat = $vatBase * $vatRate / 100;
        $vatRecoverable = (bool) ($input['vat_recoverable'] ?? false);
        if ($vatRate > 0) {
            $working[] = sprintf('VAT/GST/sales tax: %s × %s%% = %s%s', self::n($vatBase), self::n($vatRate, 3), self::n($vat), $vatRecoverable ? ' (recoverable)' : '');
        }

        $otherTaxes = [];
        $otherTotal = 0.0;
        $recoverable = $vatRecoverable ? $vat : 0.0;
        foreach ((array) ($input['other_taxes'] ?? []) as $tax) {
            if (! is_array($tax) || (float) ($tax['rate'] ?? 0) === 0.0) {
                continue;
            }
            $base = match ($tax['base'] ?? 'customs_duties_vat') {
                'customs' => $customs,
                'customs_duties' => $customs + $dutyTotal,
                default => $customs + $dutyTotal + $vat,
            };
            $amount = $base * (float) $tax['rate'] / 100;
            $name = trim((string) ($tax['name'] ?? '')) ?: 'Other tax';
            $isRecoverable = (bool) ($tax['recoverable'] ?? false);
            $working[] = sprintf('%s: %s × %s%% = %s%s', $name, self::n($base), self::n((float) $tax['rate'], 3), self::n($amount), $isRecoverable ? ' (recoverable/adjustable)' : '');
            $otherTaxes[] = ['name' => $name, 'amount' => $amount, 'recoverable' => $isRecoverable];
            $otherTotal += $amount;
            $recoverable += $isRecoverable ? $amount : 0.0;
        }

        $charges = [];
        $chargeTotal = 0.0;
        foreach ((array) ($input['charges'] ?? []) as $charge) {
            if (! is_array($charge) || (float) ($charge['amount'] ?? 0) === 0.0) {
                continue;
            }
            $perContainer = ($charge['per'] ?? 'shipment') === 'container';
            $amount = (float) $charge['amount'] * ($perContainer ? $containers : 1);
            $name = trim((string) ($charge['name'] ?? '')) ?: 'Local charge';
            $working[] = $perContainer
                ? sprintf('%s: %s per container × %s = %s', $name, self::n((float) $charge['amount']), self::n($containers), self::n($amount))
                : sprintf('%s: %s per shipment', $name, self::n($amount));
            $charges[] = ['name' => $name, 'amount' => $amount];
            $chargeTotal += $amount;
        }

        $bank = $cif * (float) ($input['bank_percent'] ?? 0) / 100;
        $financing = $cif * (float) ($input['financing_rate'] ?? 0) / 100 * (float) ($input['financing_days'] ?? 0) / 365;
        if ($bank > 0) {
            $working[] = sprintf('Bank/LC charges: CIF %s × %s%% = %s (purchase currency)', self::n($cif), self::n((float) $input['bank_percent'], 3), self::n($bank));
        }
        if ($financing > 0) {
            $working[] = sprintf('Financing: CIF %s × %s%% × %s days ÷ 365 = %s (purchase currency)', self::n($cif), self::n((float) $input['financing_rate'], 3), self::n((float) $input['financing_days']), self::n($financing));
        }

        // Everything in local currency.
        $lines = [
            ['label' => 'CIF value (goods, freight, insurance)', 'amount' => $cif * $rate, 'recoverable' => false],
            ...array_map(fn (array $d): array => ['label' => $d['name'], 'amount' => $d['amount'], 'recoverable' => false], $duties),
        ];
        if ($vat > 0) {
            $lines[] = ['label' => 'VAT / GST / sales tax', 'amount' => $vat, 'recoverable' => $vatRecoverable];
        }
        foreach ($otherTaxes as $tax) {
            $lines[] = ['label' => $tax['name'], 'amount' => $tax['amount'], 'recoverable' => $tax['recoverable']];
        }
        foreach ($charges as $charge) {
            $lines[] = ['label' => $charge['name'], 'amount' => $charge['amount'], 'recoverable' => false];
        }
        if ($bank > 0) {
            $lines[] = ['label' => 'Bank / LC charges', 'amount' => $bank * $rate, 'recoverable' => false];
        }
        if ($financing > 0) {
            $lines[] = ['label' => 'Financing', 'amount' => $financing * $rate, 'recoverable' => false];
        }

        $gross = array_sum(array_column($lines, 'amount'));
        $net = $gross - $recoverable;
        $working[] = sprintf('Gross landed cost = %s (local currency); net of recoverable taxes = %s − %s = %s', self::n($gross), self::n($gross), self::n($recoverable), self::n($net));

        $per = fn (float $total): array => [
            'shipment' => $total,
            'kg' => $total / $kg,
            'mt' => $total / $kg * 1000,
        ];

        return [
            'kg' => $kg,
            'containers' => $containers,
            'customs_value' => $customs,
            'lines' => array_map(fn (array $line): array => $line + ['per_kg' => $line['amount'] / $kg], $lines),
            'gross' => ['local' => $per($gross), 'purchase' => $per($gross / $rate)],
            'net' => ['local' => $per($net), 'purchase' => $per($net / $rate)],
            'recoverable' => $recoverable,
            'working' => $working,
        ];
    }

    protected static function n(float $value, int $decimals = 2): string
    {
        return number_format($value, $decimals);
    }
}
