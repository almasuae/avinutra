<?php

declare(strict_types=1);

namespace App\Services\Calculators;

use App\Models\CalculatorDefault;
use InvalidArgumentException;

/**
 * Tool 1 — Methionine Value Calculator (v5 §E3, v3 §7.8).
 *
 * Value factor = kg of DL-Methionine 99% replaced by 1 kg of the product as sold
 * (product basis; DL-Met 99% = 1.00).
 *
 *   cost per kg of effective methionine = price per kg ÷ value factor
 *   equivalent inclusion                = reference inclusion × reference factor ÷ factor
 *   cost per tonne of feed              = equivalent inclusion × price per kg
 *   differences vs the reference: per tonne of feed, per month, per year and in %
 */
class MethionineValueCalculator
{
    public const TOOL = 'methionine-value';

    /** Molar masses (g/mol) used to convert an equimolar efficacy into a product-basis factor. */
    public const MOLAR_MASS_METHIONINE = 149.21;

    public const MOLAR_MASS_HMTBA = 150.20;

    /**
     * Built-in presets; CRM › Website › Calculator defaults (tool "methionine-value")
     * overrides the factors and records their approval.
     *
     * @var array<string, array{label: string, factor: float|null, note: string, equimolar?: float}>
     */
    public const PRESETS = [
        'dl_met_99' => ['label' => 'DL-Methionine 99%', 'factor' => 1.00, 'note' => 'Reference product.'],
        'l_met_99' => ['label' => 'L-Methionine 99%', 'factor' => 1.00, 'note' => 'Evidence does not robustly support a premium at equal purity.'],
        'l_met_90' => ['label' => 'L-Methionine 90%', 'factor' => 0.909, 'note' => 'Purity adjustment only (0.90 ÷ 0.99).'],
        'mha_fa_88_75' => ['label' => 'MHA-FA 88% (liquid) — about 75% equimolar', 'factor' => 0.65, 'note' => 'Assumes about 75% equimolar efficacy.', 'equimolar' => 0.75],
        'mha_fa_88_80' => ['label' => 'MHA-FA 88% (liquid) — about 80% equimolar', 'factor' => 0.70, 'note' => 'Assumes about 80% equimolar efficacy, in line with the meta-analysis cited by EFSA in 2018 (79–81%).', 'equimolar' => 0.80],
        'mha_fa_88_100' => ['label' => 'MHA-FA 88% (liquid) — 100% equimolar', 'factor' => 0.88, 'note' => 'Assumes 100% equimolar efficacy: the MHA manufacturers\' position.', 'equimolar' => 1.00],
        'custom' => ['label' => 'Custom product', 'factor' => null, 'note' => 'Use the manufacturer\'s documented value.'],
    ];

    public static function costPerKgEffective(float $pricePerKg, float $factor): float
    {
        if ($factor <= 0) {
            throw new InvalidArgumentException('The value factor must be greater than zero.');
        }

        return $pricePerKg / $factor;
    }

    /**
     * The product-basis factor for MHA-FA 88% at a given equimolar efficacy
     * (shown in "Show working"): 0.88 × efficacy × (149.21 ÷ 150.20).
     */
    public static function mhaFactor(float $equimolarEfficacy, float $activeContent = 0.88): float
    {
        return $activeContent * $equimolarEfficacy * self::MOLAR_MASS_METHIONINE / self::MOLAR_MASS_HMTBA;
    }

    /**
     * The presets with factors from the database where set, and whether each is approved.
     *
     * @return array<string, array{label: string, factor: float|null, note: string, equimolar?: float, approved: bool, source: string|null, source_date: string|null}>
     */
    public static function presets(): array
    {
        try {
            $rows = CalculatorDefault::query()->where('tool', self::TOOL)->get()->keyBy('key');
        } catch (\Throwable) {
            $rows = collect();
        }

        $presets = [];

        foreach (self::PRESETS as $key => $preset) {
            /** @var CalculatorDefault|null $row */
            $row = $rows->get($key);
            $presets[$key] = [
                ...$preset,
                'factor' => $row?->value !== null ? (float) $row->value : $preset['factor'],
                'approved' => $row?->isApproved() ?? false,
                'source' => $row?->source,
                'source_date' => $row?->source_date?->format('j M Y'),
            ];
        }

        return $presets;
    }

    /**
     * @param  list<array{name: string, price: float, price_unit: string, factor: float}>  $products
     * @param  int  $reference  index of the reference product
     * @param  float  $referenceInclusion  kg of the reference product per tonne of feed
     * @param  float  $monthlyFeed  tonnes of feed per month
     * @return array{rows: list<array<string, float|string|bool>>, reference: int}
     */
    public function calculate(array $products, int $reference, float $referenceInclusion, float $monthlyFeed): array
    {
        if (! isset($products[$reference])) {
            throw new InvalidArgumentException('The reference product is missing.');
        }

        $referenceFactor = $products[$reference]['factor'];
        $rows = [];

        foreach ($products as $index => $product) {
            $pricePerKg = $product['price_unit'] === 'mt' ? $product['price'] / 1000 : $product['price'];
            $equivalentInclusion = $referenceInclusion * $referenceFactor / $product['factor'];

            $rows[] = [
                'name' => $product['name'],
                'is_reference' => $index === $reference,
                'price_per_kg' => $pricePerKg,
                'factor' => $product['factor'],
                'cost_per_kg_effective' => self::costPerKgEffective($pricePerKg, $product['factor']),
                'equivalent_inclusion' => $equivalentInclusion,
                'cost_per_mt_feed' => $equivalentInclusion * $pricePerKg,
            ];
        }

        $referenceCost = $rows[$reference]['cost_per_mt_feed'];

        foreach ($rows as $index => $row) {
            $difference = $row['cost_per_mt_feed'] - $referenceCost;
            $rows[$index] += [
                'difference_per_mt_feed' => $difference,
                'difference_per_month' => $difference * $monthlyFeed,
                'difference_per_year' => $difference * $monthlyFeed * 12,
                'difference_percent' => $referenceCost > 0 ? $difference / $referenceCost * 100 : 0.0,
            ];
        }

        return ['rows' => $rows, 'reference' => $reference];
    }
}
