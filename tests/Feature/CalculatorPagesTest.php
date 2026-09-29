<?php

declare(strict_types=1);

use App\Livewire\LandedCost;
use App\Livewire\MethionineValue;
use App\Models\CalculatorDefault;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LiteCrm\Models\Enquiry;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(DatabaseSeeder::class));

it('serves both calculators with the Technical Notice', function (string $path): void {
    $this->get($path)->assertOk()
        ->assertSee('Technical Notice')
        ->assertSee('Show working');
})->with(['/tools/methionine-value', '/tools/landed-cost']);

it('compares methionine sources with the example values', function (): void {
    Livewire::test(MethionineValue::class)
        ->call('loadExample')
        ->assertSee('Example only')
        ->assertSee('2.360')
        ->assertSee('2.708')
        ->assertSee('2.514')
        ->assertSee('4.169');
});

it('labels the MHA-FA presets with their equimolar efficacy, as indicative until approved', function (): void {
    Livewire::test(MethionineValue::class)
        ->assertSee('MHA-FA 88% (liquid) — about 75% equimolar')
        ->assertSee('MHA-FA 88% (liquid) — about 80% equimolar')
        ->assertSee('MHA-FA 88% (liquid) — 100% equimolar')
        ->assertSee('Indicative default');

    CalculatorDefault::query()->where('tool', 'methionine-value')->update(['approved_by' => 'Nutrition panel', 'approved_on' => now()]);

    Livewire::test(MethionineValue::class)->assertDontSee('Indicative default');
});

it('uses the factor from Calculator defaults', function (): void {
    CalculatorDefault::query()->where('tool', 'methionine-value')->where('key', 'mha_fa_88_80')->update(['value' => 0.72]);

    Livewire::test(MethionineValue::class)
        ->set('products', [
            ['preset' => 'dl_met_99', 'name' => '', 'price' => '2.36', 'unit' => 'kg', 'basis' => 'CFR', 'factor' => ''],
            ['preset' => 'mha_fa_88_80', 'name' => '', 'price' => '1.80', 'unit' => 'kg', 'basis' => 'CFR', 'factor' => ''],
        ])
        ->set('inclusion', '2.5')->set('monthlyFeed', '1000')
        ->assertSee('2.500'); // 1.80 ÷ 0.72
});

it('accepts a custom product with its own factor, and a second currency', function (): void {
    Livewire::test(MethionineValue::class)
        ->set('products', [
            ['preset' => 'dl_met_99', 'name' => '', 'price' => '2000', 'unit' => 'mt', 'basis' => 'CFR', 'factor' => ''],
            ['preset' => 'custom', 'name' => 'Own product', 'price' => '1.5', 'unit' => 'kg', 'basis' => 'CFR', 'factor' => '0.5'],
        ])
        ->set('inclusion', '2')->set('monthlyFeed', '100')
        ->assertSee('3.000') // 1.5 ÷ 0.5
        ->set('altCurrency', 'eur')->set('altRate', '2')->set('display', 'alt')
        ->assertSet('altCurrency', 'EUR')
        ->assertSee('6.000'); // shown in EUR at 2 per USD
});

it('explains what is missing instead of calculating', function (): void {
    Livewire::test(MethionineValue::class)
        ->assertSee('Product 1: enter a price above zero.')
        ->assertSee('Enter the current inclusion of the reference product');
});

it('pre-fills Discuss your result without sending anything', function (): void {
    $component = Livewire::test(MethionineValue::class)->call('loadExample');
    $url = $component->instance()->discussUrl();

    expect($url)->toContain('/ask-a-nutritionist')->toContain('topic=methionine');
    $this->get($url)->assertOk()->assertSee('Methionine Value Calculator');
    expect(Enquiry::query()->count())->toBe(0);
});

it('starts the landed-cost tool blank, with no country or rates', function (): void {
    $component = Livewire::test(LandedCost::class)->assertSee('Enter the price.');

    expect($component->get('form')['local_currency'])->toBe('')
        ->and($component->get('form')['vat_rate'])->toBe('')
        ->and(collect($component->get('form')['duties'])->pluck('rate')->filter()->all())->toBe([]);
});

it('loads illustrative example values, clearly labelled', function (): void {
    Livewire::test(LandedCost::class)
        ->call('loadExample')
        ->assertSee('Example only — illustrative values, not the rates of any country.')
        ->assertSee('Gross (USD)')
        ->assertSee('Net of recoverable taxes (EUR)');
});

it('reproduces the landed-cost reference test through the tool', function (): void {
    Livewire::test(LandedCost::class)
        ->set('form', [
            ...LandedCost::blank(),
            'quantity' => '20000', 'quantity_unit' => 'kg', 'kg_per_container' => '20000',
            'purchase_currency' => 'USD', 'local_currency' => 'LCU', 'exchange_rate' => '277.20',
            'price' => '2.80', 'basis' => 'CFR', 'insurance_type' => 'percent', 'insurance_value' => '0.35',
            'duties' => [],
            'vat_rate' => '18', 'vat_base' => 'customs_duties', 'vat_recoverable' => true,
            'other_taxes' => [['name' => 'Other import tax', 'rate' => '2', 'base' => 'customs_duties_vat', 'recoverable' => true]],
            'charges' => [['name' => 'Fixed local charges', 'amount' => '400000', 'per' => 'container']],
        ])
        ->assertSee('3.454')
        ->assertSee('2.882')
        ->set('working', true)
        ->assertSee('Customs value (CIF basis) = CIF value');
});

it('asks for freight and insurance only when the price basis needs them', function (): void {
    Livewire::test(LandedCost::class)
        ->set('form.basis', 'FOB')->assertSee('Freight (USD)')->assertSee('Insurance as')
        ->set('form.basis', 'CFR')->assertDontSee('Freight (USD)')->assertSee('Insurance as')
        ->set('form.basis', 'DAP')->assertDontSee('Insurance as')->assertSee('already includes freight and insurance');
});

it('adds and removes duty, tax and charge rows', function (): void {
    $component = Livewire::test(LandedCost::class)
        ->call('addRow', 'duties')
        ->call('addRow', 'other_taxes')
        ->call('removeRow', 'charges', 0);

    expect($component->get('form')['duties'])->toHaveCount(2)
        ->and($component->get('form')['other_taxes'])->toHaveCount(1)
        ->and($component->get('form')['charges'])->toHaveCount(count(LandedCost::CHARGE_NAMES) - 1);
});
