<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\Calculators\MethionineValueCalculator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Tool 1 — Methionine Value Calculator. The inputs live in the URL, so a result
 * can be shared; nothing is stored unless the visitor sends a form.
 *
 * @property-read array<string, array<string, mixed>> $presets
 * @property-read array{result: array<string, mixed>|null, problems: list<string>, rate: float, currency: string} $outcome
 */
class MethionineValue extends Component
{
    public const MIN_PRODUCTS = 2;

    public const MAX_PRODUCTS = 4;

    /** @var list<array{preset: string, name: string, price: string, unit: string, basis: string, factor: string}> */
    #[Url(as: 'p')]
    public array $products = [];

    #[Url(as: 'ref')]
    public int $reference = 0;

    #[Url(as: 'inc')]
    public string $inclusion = '';

    #[Url(as: 'feed')]
    public string $monthlyFeed = '';

    #[Url(as: 'cur')]
    public string $currency = 'USD';

    #[Url(as: 'alt')]
    public string $altCurrency = '';

    #[Url(as: 'fx')]
    public string $altRate = '';

    #[Url(as: 'fxd')]
    public string $altRateDate = '';

    #[Url(as: 'show')]
    public string $display = 'main';

    #[Url(as: 'working')]
    public bool $working = false;

    public bool $example = false;

    public function mount(): void
    {
        if ($this->products === []) {
            $this->products = [self::row('dl_met_99'), self::row('mha_fa_88_80')];
        }

        // The rows may come from a shared URL: keep only well-formed values.
        /** @var array<mixed> $raw */
        $raw = $this->products;
        $rows = [];
        foreach (array_slice($raw, 0, self::MAX_PRODUCTS) as $row) {
            $rows[] = self::clean(is_array($row) ? $row : []);
        }
        $this->products = $rows;

        while (count($this->products) < self::MIN_PRODUCTS) {
            $this->products[] = self::row('dl_met_99');
        }

        $this->reference = min(max(0, $this->reference), count($this->products) - 1);
    }

    /**
     * @return array{preset: string, name: string, price: string, unit: string, basis: string, factor: string}
     */
    protected static function row(string $preset, string $price = ''): array
    {
        return ['preset' => $preset, 'name' => '', 'price' => $price, 'unit' => 'kg', 'basis' => 'CFR', 'factor' => ''];
    }

    /**
     * @param  array<mixed>  $row
     * @return array{preset: string, name: string, price: string, unit: string, basis: string, factor: string}
     */
    protected static function clean(array $row): array
    {
        $preset = (string) ($row['preset'] ?? 'dl_met_99');

        return [
            'preset' => array_key_exists($preset, MethionineValueCalculator::PRESETS) ? $preset : 'dl_met_99',
            'name' => mb_substr((string) ($row['name'] ?? ''), 0, 80),
            'price' => (string) ($row['price'] ?? ''),
            'unit' => ($row['unit'] ?? 'kg') === 'mt' ? 'mt' : 'kg',
            'basis' => ($row['basis'] ?? 'CFR') === 'landed' ? 'landed' : 'CFR',
            'factor' => (string) ($row['factor'] ?? ''),
        ];
    }

    public function addProduct(): void
    {
        if (count($this->products) < self::MAX_PRODUCTS) {
            $this->products[] = self::row('mha_fa_88_75');
        }
    }

    public function removeProduct(int $index): void
    {
        if (count($this->products) > self::MIN_PRODUCTS && isset($this->products[$index])) {
            unset($this->products[$index]);
            $this->products = array_values($this->products);
            $this->reference = $this->reference === $index ? 0 : ($this->reference > $index ? $this->reference - 1 : $this->reference);
        }
    }

    /**
     * Illustrative values ("Example only"): the reference prices of the methionine guide.
     */
    public function loadExample(): void
    {
        $this->products = [self::row('dl_met_99', '2.36'), self::row('mha_fa_88_75', '1.76'), self::row('mha_fa_88_80', '1.76'), self::row('l_met_90', '3.79')];
        $this->reference = 0;
        $this->inclusion = '2.5';
        $this->monthlyFeed = '5000';
        $this->currency = 'USD';
        $this->example = true;
    }

    public function resetForm(): void
    {
        $this->products = [self::row('dl_met_99'), self::row('mha_fa_88_80')];
        $this->reference = 0;
        $this->inclusion = '';
        $this->monthlyFeed = '';
        $this->currency = 'USD';
        $this->altCurrency = '';
        $this->altRate = '';
        $this->altRateDate = '';
        $this->display = 'main';
        $this->example = false;
    }

    public function updatedCurrency(): void
    {
        $this->currency = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $this->currency) ?? '', 0, 3));
    }

    public function updatedAltCurrency(): void
    {
        $this->altCurrency = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $this->altCurrency) ?? '', 0, 3));
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    #[Computed]
    public function presets(): array
    {
        return MethionineValueCalculator::presets();
    }

    /**
     * The value factor of a row: the preset's, or the visitor's own for a custom product.
     */
    public function factorFor(int $index): ?float
    {
        $row = $this->products[$index] ?? null;

        if ($row === null) {
            return null;
        }

        $factor = $row['preset'] === 'custom' ? (float) $row['factor'] : ($this->presets[$row['preset']]['factor'] ?? null);

        return $factor !== null && $factor > 0 ? (float) $factor : null;
    }

    /**
     * @return array{result: array<string, mixed>|null, problems: list<string>, rate: float, currency: string}
     */
    #[Computed]
    public function outcome(): array
    {
        $problems = [];
        $products = [];

        foreach ($this->products as $index => $row) {
            $label = 'Product '.($index + 1);
            $price = is_numeric($row['price']) ? (float) $row['price'] : null;
            $factor = $this->factorFor($index);

            if ($price === null || $price <= 0) {
                $problems[] = "{$label}: enter a price above zero.";
            }
            if ($factor === null) {
                $problems[] = "{$label}: enter a value factor above zero.";
            }

            $name = $row['preset'] === 'custom' ? (trim($row['name']) ?: 'Custom product') : $this->presets[$row['preset']]['label'];
            $products[] = ['name' => $name, 'price' => (float) $price, 'price_unit' => $row['unit'], 'factor' => (float) $factor];
        }

        if (! is_numeric($this->inclusion) || (float) $this->inclusion <= 0) {
            $problems[] = 'Enter the current inclusion of the reference product (kg per tonne of feed).';
        }
        if (! is_numeric($this->monthlyFeed) || (float) $this->monthlyFeed < 0) {
            $problems[] = 'Enter your monthly feed production (tonnes).';
        }

        $useAlt = $this->display === 'alt' && is_numeric($this->altRate) && (float) $this->altRate > 0 && strlen($this->altCurrency) === 3;
        $rate = $useAlt ? (float) $this->altRate : 1.0;
        $currency = $useAlt ? $this->altCurrency : ($this->currency ?: 'USD');

        if ($problems !== []) {
            return ['result' => null, 'problems' => $problems, 'rate' => $rate, 'currency' => $currency];
        }

        return [
            'result' => (new MethionineValueCalculator)->calculate($products, $this->reference, (float) $this->inclusion, (float) $this->monthlyFeed),
            'problems' => [],
            'rate' => $rate,
            'currency' => $currency,
        ];
    }

    /**
     * Pre-fills the Ask-a-Nutritionist form; nothing is sent until the visitor submits it.
     */
    public function discussUrl(): ?string
    {
        if (! Route::has('ask-a-nutritionist')) {
            return null;
        }

        $lines = ['Methionine Value Calculator — my inputs:'];
        foreach ($this->products as $index => $row) {
            $name = $row['preset'] === 'custom' ? (trim($row['name']) ?: 'Custom product') : $this->presets[$row['preset']]['label'];
            $lines[] = sprintf('- %s: %s %s per %s (%s), value factor %s', $name, $row['price'] ?: '?', $this->currency, $row['unit'] === 'mt' ? 'tonne' : 'kg', $row['basis'] === 'landed' ? 'landed' : 'CFR', $this->factorFor($index) ?? '?');
        }
        $lines[] = sprintf('Reference inclusion: %s kg/t; feed production: %s t/month.', $this->inclusion ?: '?', $this->monthlyFeed ?: '?');

        return route('ask-a-nutritionist', ['topic' => 'methionine', 'message' => implode("\n", $lines)]);
    }

    public function render(): View
    {
        return view('livewire.methionine-value');
    }
}
