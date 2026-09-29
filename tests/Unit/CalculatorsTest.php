<?php

declare(strict_types=1);

use App\Services\Calculators\LandedCostCalculator;
use App\Services\Calculators\MethionineValueCalculator;

/*
 * Reference tests (CLAUDE.md, v5 §E3, owner's answers of 29 Sep 2026). They must always pass.
 */

it('computes the cost per kg of effective methionine for the reference values', function (float $price, float $factor, string $expected): void {
    expect(number_format(MethionineValueCalculator::costPerKgEffective($price, $factor), 3))->toBe($expected);
})->with([
    'DL-Met 99%' => [2.36, 1.00, '2.360'],
    'MHA-FA 88%, 0.65' => [1.76, 0.65, '2.708'],
    'MHA-FA 88%, 0.70' => [1.76, 0.70, '2.514'],
    'MHA-FA 88%, 0.88' => [1.76, 0.88, '2.000'],
    'L-Met 90%, 0.909' => [3.79, 0.909, '4.169'],
]);

it('offers the three MHA-FA presets on a product basis, each with its equimolar efficacy', function (): void {
    $presets = MethionineValueCalculator::PRESETS;

    expect($presets['mha_fa_88_75']['factor'])->toBe(0.65)
        ->and($presets['mha_fa_88_80']['factor'])->toBe(0.70)
        ->and($presets['mha_fa_88_100']['factor'])->toBe(0.88)
        ->and($presets['l_met_90']['factor'])->toBe(0.909)
        // The conversion shown in "Show working": 0.88 × efficacy × 149.21 ÷ 150.20.
        ->and(round(MethionineValueCalculator::mhaFactor(0.80), 2))->toBe(0.70)
        ->and(round(MethionineValueCalculator::mhaFactor(0.75), 2))->toBe(0.66);
});

it('compares products per tonne of feed, per month and per year', function (): void {
    $result = (new MethionineValueCalculator)->calculate([
        ['name' => 'DL-Met 99%', 'price' => 2360, 'price_unit' => 'mt', 'factor' => 1.00],
        ['name' => 'MHA-FA 88%', 'price' => 1.76, 'price_unit' => 'kg', 'factor' => 0.65],
    ], reference: 0, referenceInclusion: 2.5, monthlyFeed: 1000);

    [$reference, $mha] = $result['rows'];

    expect($reference['price_per_kg'])->toBe(2.36)
        ->and(round($reference['cost_per_mt_feed'], 3))->toBe(5.900)
        ->and(round($mha['equivalent_inclusion'], 4))->toBe(3.8462)
        ->and(round($mha['cost_per_mt_feed'], 3))->toBe(6.769)
        ->and(round($mha['difference_per_month'], 2))->toBe(869.23)
        ->and(round($mha['difference_per_year'], 2))->toBe(10430.77)
        ->and(round($mha['difference_percent'], 2))->toBe(14.73);
});

it('matches the landed-cost reference test with universal inputs', function (): void {
    $result = (new LandedCostCalculator)->calculate([
        'quantity' => 20000, 'quantity_unit' => 'kg', 'kg_per_container' => 20000,
        'exchange_rate' => 277.20,
        'price' => 2.80, 'price_unit' => 'kg', 'basis' => 'CFR',
        'insurance_type' => 'percent', 'insurance_value' => 0.35,
        'valuation' => 'CIF',
        'duties' => [],
        'vat_rate' => 18, 'vat_base' => 'customs_duties', 'vat_recoverable' => true,
        'other_taxes' => [['name' => 'Other import tax', 'rate' => 2, 'base' => 'customs_duties_vat', 'recoverable' => true]],
        'charges' => [['name' => 'Fixed local charges', 'amount' => 400000, 'per' => 'container']],
        'bank_percent' => 0, 'financing_rate' => 0, 'financing_days' => 0,
    ]);

    expect(round($result['gross']['purchase']['kg'], 3))->toBe(3.454)
        ->and(round($result['net']['purchase']['kg'], 3))->toBe(2.882)
        ->and($result['containers'])->toBe(1.0);
});

it('adds freight and insurance only when the price basis needs them', function (string $basis, bool $freight, bool $insurance): void {
    expect(LandedCostCalculator::needsFreight($basis))->toBe($freight)
        ->and(LandedCostCalculator::needsInsurance($basis))->toBe($insurance);
})->with([
    ['EXW', true, true], ['FOB', true, true], ['CFR', false, true], ['CPT', false, true],
    ['CIF', false, false], ['CIP', false, false], ['DAP', false, false],
]);

it('applies duty lines on the customs value or cumulatively, and fixed duties per tonne', function (): void {
    $base = [
        'quantity' => 10, 'quantity_unit' => 'mt', 'kg_per_container' => 20000, 'exchange_rate' => 1,
        'price' => 1000, 'price_unit' => 'mt', 'basis' => 'CIF', 'valuation' => 'CIF',
    ];
    $calculator = new LandedCostCalculator;

    $separate = $calculator->calculate([...$base, 'duties' => [
        ['name' => 'Duty A', 'rate' => 10, 'type' => 'percent', 'base' => 'customs'],
        ['name' => 'Duty B', 'rate' => 10, 'type' => 'percent', 'base' => 'customs'],
    ]]);
    $cumulative = $calculator->calculate([...$base, 'duties' => [
        ['name' => 'Duty A', 'rate' => 10, 'type' => 'percent', 'base' => 'customs'],
        ['name' => 'Duty B', 'rate' => 10, 'type' => 'percent', 'base' => 'cumulative'],
    ]]);
    $fixed = $calculator->calculate([...$base, 'duties' => [['name' => 'Levy', 'rate' => 5, 'type' => 'per_mt', 'base' => 'customs']]]);

    expect($separate['gross']['local']['shipment'])->toBe(12000.0)
        ->and($cumulative['gross']['local']['shipment'])->toBe(12100.0)
        ->and($fixed['gross']['local']['shipment'])->toBe(10050.0);
});

it('values customs on FOB when chosen, and charges bank and financing on CIF', function (): void {
    $result = (new LandedCostCalculator)->calculate([
        'quantity' => 1000, 'quantity_unit' => 'kg', 'kg_per_container' => 20000, 'exchange_rate' => 2,
        'price' => 1, 'price_unit' => 'kg', 'basis' => 'FOB',
        'freight_amount' => 100, 'freight_per' => 'shipment',
        'insurance_type' => 'amount', 'insurance_value' => 10,
        'valuation' => 'FOB',
        'duties' => [['name' => 'Duty', 'rate' => 10, 'type' => 'percent', 'base' => 'customs']],
        'bank_percent' => 1, 'financing_rate' => 10, 'financing_days' => 73,
    ]);

    // CIF = 1000 + 100 + 10 = 1110; customs (FOB) = 1000 × 2 = 2000; duty 200 local.
    expect($result['customs_value'])->toBe(2000.0)
        ->and(round($result['gross']['local']['shipment'], 2))->toBe(round(1110 * 2 + 200 + 11.10 * 2 + 1110 * 0.1 * 73 / 365 * 2, 2));
});

it('refuses to calculate without a quantity or an exchange rate', function (array $input): void {
    (new LandedCostCalculator)->calculate($input);
})->with([
    [['quantity' => 0, 'exchange_rate' => 1, 'basis' => 'CIF']],
    [['quantity' => 100, 'exchange_rate' => 0, 'basis' => 'CIF']],
])->throws(InvalidArgumentException::class);
