<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Widgets;

use Filament\Facades\Filament;
use Filament\Widgets\Widget;
use LiteCrm\Enums\TrialStatus;
use LiteCrm\Filament\Widgets\Concerns\CrmWidget;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Sample;
use LiteCrm\Models\Trial;
use LiteCrm\Support\Permissions;

/**
 * Samples awaiting feedback and trials planned or running.
 */
class SamplesTrialsWidget extends Widget
{
    use CrmWidget;

    protected static ?int $sort = 8;

    protected string $view = 'lite-crm::filament.widgets.samples-trials';

    protected static function module(): string
    {
        return 'samples';
    }

    public static function canView(): bool
    {
        $user = Filament::auth()->user();

        return (LiteCrm::isModuleEnabled('samples') && Permissions::allows($user, 'samples.view'))
            || (LiteCrm::isModuleEnabled('trials') && Permissions::allows($user, 'trials.view'));
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $user = Filament::auth()->user();

        return [
            'samples' => LiteCrm::isModuleEnabled('samples') && Permissions::allows($user, 'samples.view')
                ? $this->scoped(Sample::class)->with(['organisation', 'product'])->scopes(['inProgress'])->latest()->limit(8)->get()
                : null,
            'trials' => LiteCrm::isModuleEnabled('trials') && Permissions::allows($user, 'trials.view')
                ? $this->scoped(Trial::class)->with('organisation')->whereIn('status', [TrialStatus::Planned->value, TrialStatus::Running->value])->orderBy('start_date')->limit(8)->get()
                : null,
        ];
    }
}
