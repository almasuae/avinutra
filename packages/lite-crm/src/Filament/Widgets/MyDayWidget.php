<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Widgets;

use Filament\Facades\Filament;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Date;
use LiteCrm\Filament\Widgets\Concerns\CrmWidget;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Opportunity;
use LiteCrm\Models\Task;
use LiteCrm\Support\Visibility;

/**
 * My tasks due today or overdue, and my next steps this week.
 */
class MyDayWidget extends Widget
{
    use CrmWidget;

    protected static ?int $sort = 1;

    protected string $view = 'lite-crm::filament.widgets.my-day';

    protected static function module(): string
    {
        return 'tasks';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $me = Filament::auth()->id();

        $tasks = Visibility::apply(LiteCrm::model(Task::class)::query(), $this->user())
            ->scopes(['open'])
            ->where('assignee_id', $me)
            ->whereNotNull('due_at')
            ->where('due_at', '<=', Date::now()->endOfDay())
            ->orderBy('due_at')
            ->limit(10)
            ->get();

        $nextSteps = LiteCrm::isModuleEnabled('opportunities')
            ? Visibility::apply(LiteCrm::model(Opportunity::class)::query(), $this->user())
                ->where('owner_id', $me)
                ->whereNull('closed_at')
                ->whereNotNull('next_step_date')
                ->whereDate('next_step_date', '<=', Date::today()->addDays(7))
                ->orderBy('next_step_date')
                ->limit(10)
                ->get()
            : collect();

        return ['tasks' => $tasks, 'nextSteps' => $nextSteps];
    }
}
