<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Date;
use LiteCrm\Filament\Widgets\Concerns\CrmWidget;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Opportunity;
use LiteCrm\Support\Money;

/**
 * Won and lost opportunities this quarter (or in the chosen period), with lost reasons.
 */
class WonLostWidget extends Widget
{
    use CrmWidget;

    protected static ?int $sort = 4;

    protected string $view = 'lite-crm::filament.widgets.won-lost';

    protected static function module(): string
    {
        return 'opportunities';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        [$from, $until] = $this->period(Date::now()->firstOfQuarter());
        $territory = $this->filter('territory_id');

        $closed = $this->scoped(Opportunity::class)
            ->with(['stage', 'lostReason'])
            ->whereBetween('closed_at', [$from, $until])
            ->when($territory, fn (Builder $query) => $query->whereHas('organisation', fn (Builder $organisations) => $organisations->where('territory_id', $territory)))
            ->get();

        $won = $closed->filter(fn (Opportunity $opportunity): bool => $opportunity->stage->is_won);
        $lost = $closed->filter(fn (Opportunity $opportunity): bool => $opportunity->stage->is_lost);
        $sum = fn ($items): float => (float) $items->sum(fn (Opportunity $opportunity): float => (float) Money::toBase($opportunity->value, $opportunity->currency));

        return [
            'from' => $from,
            'until' => $until,
            'won' => ['count' => $won->count(), 'value' => $sum($won)],
            'lost' => ['count' => $lost->count(), 'value' => $sum($lost)],
            'reasons' => $lost->groupBy(fn (Opportunity $opportunity): string => $opportunity->lostReason->label ?? __('lite-crm::dashboard.no_reason'))
                ->map(fn ($items): int => $items->count())
                ->sortDesc()
                ->all(),
            'base' => LiteCrm::baseCurrency(),
        ];
    }
}
