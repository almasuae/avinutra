<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Date;
use LiteCrm\Filament\Widgets\Concerns\CrmWidget;
use LiteCrm\LiteCrm;
use LiteCrm\Models\PriceEntry;
use LiteCrm\Models\Product;
use LiteCrm\Support\Money;

/**
 * Twelve months of prices for one product: the monthly average per basis, in
 * the base currency (entries without an exchange rate are left out).
 */
class PriceWatchWidget extends ChartWidget
{
    use CrmWidget;

    protected static ?int $sort = 7;

    protected ?string $maxHeight = '260px';

    protected static function module(): string
    {
        return 'price_log';
    }

    public function getHeading(): string|Htmlable|null
    {
        return __('lite-crm::dashboard.price_watch.heading', ['currency' => LiteCrm::baseCurrency()]);
    }

    protected function getType(): string
    {
        return 'line';
    }

    /**
     * Products that have price entries.
     *
     * @return array<int|string, string>
     */
    protected function getFilters(): ?array
    {
        /** @var array<int|string, string> $options */
        $options = LiteCrm::model(Product::class)::query()
            ->whereIn('id', $this->scoped(PriceEntry::class, null)->select('product_id'))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();

        return $options;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $productId = $this->filter ?? array_key_first($this->getFilters() ?? []);
        $months = collect(range(11, 0))->map(fn (int $ago) => Date::now()->startOfMonth()->subMonths($ago));

        if ($productId === null) {
            return ['datasets' => [], 'labels' => $months->map->format('M Y')->all()];
        }

        $entries = $this->scoped(PriceEntry::class, null)
            ->where('product_id', $productId)
            ->whereDate('observed_on', '>=', $months->first())
            ->get();

        $datasets = [];

        foreach ($entries->groupBy(fn (PriceEntry $entry): string => $entry->basis->value) as $basis => $byBasis) {
            $data = [];

            foreach ($months as $month) {
                $prices = $byBasis
                    ->filter(fn (PriceEntry $entry): bool => $entry->observed_on->isSameMonth($month))
                    ->map(fn (PriceEntry $entry): ?float => Money::toBase($entry->price, $entry->currency, $entry->observed_on))
                    ->filter(fn (?float $price): bool => $price !== null);

                $data[] = $prices->isEmpty() ? null : round((float) $prices->avg(), 4);
            }

            $datasets[] = ['label' => (string) $basis, 'data' => $data, 'spanGaps' => true];
        }

        return ['datasets' => $datasets, 'labels' => $months->map->format('M Y')->all()];
    }
}
