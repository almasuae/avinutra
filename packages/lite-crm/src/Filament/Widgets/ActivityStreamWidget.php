<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Widgets;

use Filament\Widgets\Widget;
use LiteCrm\Filament\Widgets\Concerns\CrmWidget;
use LiteCrm\Models\Activity;

/**
 * The latest calls, meetings and notes the user may see.
 */
class ActivityStreamWidget extends Widget
{
    use CrmWidget;

    protected static ?int $sort = 11;

    protected string $view = 'lite-crm::filament.widgets.activity-stream';

    protected static function module(): string
    {
        return 'activities';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'activities' => $this->scoped(Activity::class)
                ->with(['type', 'owner', 'subject'])
                ->latest('occurred_at')
                ->limit(12)
                ->get(),
        ];
    }
}
