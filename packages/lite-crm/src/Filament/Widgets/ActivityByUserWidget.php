<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Date;
use LiteCrm\Filament\Widgets\Concerns\CrmWidget;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Activity;

/**
 * Activities logged per user over the last 30 days (or the chosen period).
 */
class ActivityByUserWidget extends ChartWidget
{
    use CrmWidget;

    protected static ?int $sort = 5;

    protected ?string $maxHeight = '260px';

    protected static function module(): string
    {
        return 'activities';
    }

    public function getHeading(): string|Htmlable|null
    {
        return __('lite-crm::dashboard.activity_by_user.heading');
    }

    protected function getType(): string
    {
        return 'bar';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        [$from, $until] = $this->period(Date::now()->subDays(30));

        $counts = $this->scoped(Activity::class)
            ->whereBetween('occurred_at', [$from, $until])
            ->whereNotNull('owner_id')
            ->selectRaw('owner_id, COUNT(*) as total')
            ->groupBy('owner_id')
            ->pluck('total', 'owner_id');

        $names = LiteCrm::userModel()::query()->whereKey($counts->keys()->all())->pluck('name', 'id');

        return [
            'datasets' => [[
                'label' => __('lite-crm::dashboard.activity_by_user.label'),
                'data' => $counts->values()->map(fn (mixed $total): int => (int) $total)->all(),
            ]],
            'labels' => $counts->keys()->map(fn (mixed $id): string => (string) ($names[$id] ?? '#'.$id))->all(),
        ];
    }
}
