<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\Calculators\LandedCostCalculator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;
use InvalidArgumentException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Tool 2 — Landed Cost Calculator (universal). Starts blank; "Example"
 * fills illustrative values that are not the rates of any country. The inputs
 * live in the URL so a result can be shared; nothing is stored.
 */
class LandedCost extends Component
{
    public const CHARGE_NAMES = [
        'Port / terminal handling',
        'Customs broker / clearing',
        'Inspection / lab testing',
        'Documentation',
        'Storage / demurrage',
        'Inland transport',
    ];

    /** @var array<string, mixed> */
    #[Url(as: 'lc')]
    public array $form = [];

    #[Url(as: 'working')]
    public bool $working = false;

    public bool $example = false;

    public function mount(): void
    {
        $this->form = array_replace(self::blank(), array_intersect_key($this->form, self::blank()));
    }

    /**
     * @return array<string, mixed>
     */
    public static function blank(): array
    {
        return [
            'quantity' => '', 'quantity_unit' => 'kg', 'kg_per_bag' => '25', 'kg_per_container' => '',
            'purchase_currency' => 'USD', 'local_currency' => '', 'exchange_rate' => '', 'rate_date' => '',
            'price' => '', 'price_unit' => 'kg', 'basis' => 'CIF',
            'freight_amount' => '', 'freight_per' => 'shipment',
            'insurance_type' => 'percent', 'insurance_value' => '',
            'valuation' => 'CIF', 'freight_included' => '', 'insurance_included' => '',
            'duties' => [['name' => 'Customs duty', 'rate' => '', 'type' => 'percent', 'base' => 'customs']],
            'vat_rate' => '', 'vat_base' => 'customs_duties', 'vat_recoverable' => false,
            'other_taxes' => [],
            'charges' => array_map(fn (string $name): array => ['name' => $name, 'amount' => '', 'per' => 'shipment'], self::CHARGE_NAMES),
            'bank_percent' => '', 'financing_rate' => '', 'financing_days' => '',
        ];
    }

    /**
     * Illustrative values only — deliberately not the rates of any real country.
     */
    public function loadExample(): void
    {
        $this->form = [
            ...self::blank(),
            'quantity' => '20', 'quantity_unit' => 'mt', 'kg_per_container' => '20000',
            'purchase_currency' => 'USD', 'local_currency' => 'EUR', 'exchange_rate' => '0.92', 'rate_date' => now()->toDateString(),
            'price' => '2.80', 'price_unit' => 'kg', 'basis' => 'CFR',
            'insurance_type' => 'percent', 'insurance_value' => '0.35',
            'duties' => [['name' => 'Customs duty', 'rate' => '5', 'type' => 'percent', 'base' => 'customs']],
            'vat_rate' => '20', 'vat_base' => 'customs_duties', 'vat_recoverable' => true,
            'charges' => array_map(fn (string $name, string $amount): array => ['name' => $name, 'amount' => $amount, 'per' => 'container'], self::CHARGE_NAMES, ['450', '250', '150', '80', '0', '600']),
            'bank_percent' => '0.5', 'financing_rate' => '8', 'financing_days' => '60',
        ];
        $this->example = true;
    }

    public function resetForm(): void
    {
        $this->form = self::blank();
        $this->example = false;
    }

    public function addRow(string $list): void
    {
        if (! in_array($list, ['duties', 'other_taxes', 'charges'], true) || count($this->form[$list] ?? []) >= 10) {
            return;
        }

        $this->form[$list][] = match ($list) {
            'duties' => ['name' => '', 'rate' => '', 'type' => 'percent', 'base' => 'customs'],
            'other_taxes' => ['name' => '', 'rate' => '', 'base' => 'customs_duties_vat', 'recoverable' => false],
            default => ['name' => '', 'amount' => '', 'per' => 'shipment'],
        };
    }

    public function removeRow(string $list, int $index): void
    {
        if (in_array($list, ['duties', 'other_taxes', 'charges'], true) && isset($this->form[$list][$index])) {
            unset($this->form[$list][$index]);
            $this->form[$list] = array_values($this->form[$list]);
        }
    }

    public function updatedForm(mixed $value, ?string $key = null): void
    {
        if (in_array($key, ['purchase_currency', 'local_currency'], true)) {
            $this->form[$key] = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', (string) $value) ?? '', 0, 3));
        }
    }

    /**
     * @return array{result: array<string, mixed>|null, problem: string|null}
     */
    #[Computed]
    public function outcome(): array
    {
        $form = $this->form;

        if (! is_numeric($form['price']) || (float) $form['price'] <= 0) {
            return ['result' => null, 'problem' => 'Enter the price.'];
        }
        if (strlen((string) $form['local_currency']) !== 3 || strlen((string) $form['purchase_currency']) !== 3) {
            return ['result' => null, 'problem' => 'Enter the purchase and local currencies (three-letter ISO codes).'];
        }
        if ($form['quantity_unit'] === 'containers' && (! is_numeric($form['kg_per_container']) || (float) $form['kg_per_container'] <= 0)) {
            return ['result' => null, 'problem' => 'Enter the net kg per container.'];
        }

        try {
            return ['result' => (new LandedCostCalculator)->calculate($form), 'problem' => null];
        } catch (InvalidArgumentException $exception) {
            return ['result' => null, 'problem' => $exception->getMessage()];
        }
    }

    public function discussUrl(): ?string
    {
        return Route::has('ask-a-nutritionist')
            ? route('ask-a-nutritionist', ['topic' => 'other', 'message' => 'Landed Cost Calculator — I would like to discuss this calculation: '.route('tools.landed-cost', ['lc' => $this->form])])
            : null;
    }

    public function render(): View
    {
        return view('livewire.landed-cost', [
            'needsFreight' => LandedCostCalculator::needsFreight((string) $this->form['basis']),
            'needsInsurance' => LandedCostCalculator::needsInsurance((string) $this->form['basis']),
        ]);
    }
}
