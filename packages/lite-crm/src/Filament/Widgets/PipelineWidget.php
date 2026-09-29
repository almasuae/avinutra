<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use LiteCrm\Filament\Resources\Opportunities\OpportunityResource;
use LiteCrm\Filament\Widgets\Concerns\CrmWidget;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Opportunity;
use LiteCrm\Models\PipelineStage;
use LiteCrm\Support\Money;

/**
 * Open opportunities by stage for the first pipelines (default two): count,
 * value and weighted value in the base currency.
 */
class PipelineWidget extends Widget
{
    use CrmWidget;

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'lite-crm::filament.widgets.pipeline';

    protected static function module(): string
    {
        return 'opportunities';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $pipelines = array_slice(OpportunityResource::pipelineOptions(), 0, (int) config('lite-crm.dashboard.pipelines', 2), true);
        $territory = $this->filter('territory_id');
        $summaries = [];
        $missingRates = false;

        foreach ($pipelines as $pipelineId => $name) {
            $opportunities = $this->scoped(Opportunity::class)
                ->where('pipeline_id', $pipelineId)
                ->whereNull('closed_at')
                ->when($territory, fn (Builder $query) => $query->whereHas('organisation', fn (Builder $organisations) => $organisations->where('territory_id', $territory)))
                ->get();

            $rows = [];
            $totals = ['count' => 0, 'value' => 0.0, 'weighted' => 0.0];

            foreach (LiteCrm::model(PipelineStage::class)::query()->where('pipeline_id', $pipelineId)->where('is_won', false)->where('is_lost', false)->orderBy('sort')->get() as $stage) {
                $inStage = $opportunities->where('stage_id', $stage->getKey());
                $value = 0.0;
                $weighted = 0.0;

                foreach ($inStage as $opportunity) {
                    $base = Money::toBase($opportunity->value, $opportunity->currency);

                    if ($opportunity->value !== null && $base === null) {
                        $missingRates = true;

                        continue;
                    }

                    $value += (float) $base;
                    $weighted += (float) $base * $opportunity->probability / 100;
                }

                $rows[] = ['stage' => $stage->name, 'count' => $inStage->count(), 'value' => $value, 'weighted' => $weighted];
                $totals['count'] += $inStage->count();
                $totals['value'] += $value;
                $totals['weighted'] += $weighted;
            }

            $summaries[] = ['name' => $name, 'rows' => $rows, 'totals' => $totals];
        }

        return ['summaries' => $summaries, 'base' => LiteCrm::baseCurrency(), 'missingRates' => $missingRates];
    }
}
