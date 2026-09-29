<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Pages;

use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use LiteCrm\Filament\Widgets;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Lookup;
use LiteCrm\Support\Permissions;

/**
 * The CRM home page. Widgets can be switched off per site in
 * lite-crm.dashboard_widgets; Managers and Admins can filter by user,
 * territory and date range.
 */
class CrmDashboard extends Dashboard
{
    use HasFiltersForm;

    /** Widget classes, keyed by the name used in lite-crm.dashboard_widgets. */
    public const WIDGETS = [
        'my_day' => Widgets\MyDayWidget::class,
        'enquiries' => Widgets\EnquiriesWidget::class,
        'pipelines' => Widgets\PipelineWidget::class,
        'won_lost' => Widgets\WonLostWidget::class,
        'activity_by_user' => Widgets\ActivityByUserWidget::class,
        'expiring_documents' => Widgets\ExpiringDocumentsWidget::class,
        'price_watch' => Widgets\PriceWatchWidget::class,
        'samples_trials' => Widgets\SamplesTrialsWidget::class,
        'team_clock' => Widgets\TeamClockWidget::class,
        'notices' => Widgets\NoticesWidget::class,
        'activity_stream' => Widgets\ActivityStreamWidget::class,
    ];

    public function getTitle(): string|Htmlable
    {
        return __('lite-crm::dashboard.title');
    }

    public static function canFilter(): bool
    {
        return Permissions::allows(Filament::auth()->user(), 'dashboard.filter');
    }

    public function getWidgets(): array
    {
        /** @var array<string, bool> $enabled */
        $enabled = config('lite-crm.dashboard_widgets', []);

        return array_values(array_filter(
            self::WIDGETS,
            fn (string $widget, string $key): bool => ($enabled[$key] ?? true),
            ARRAY_FILTER_USE_BOTH,
        ));
    }

    public function filtersForm(Schema $schema): Schema
    {
        if (! static::canFilter()) {
            return $schema->components([]);
        }

        return $schema->components([
            Select::make('owner_id')
                ->label(__('lite-crm::dashboard.filters.user'))
                ->options(fn (): array => LiteCrm::userOptions())
                ->placeholder(__('lite-crm::dashboard.filters.everyone')),
            Select::make('territory_id')
                ->label(__('lite-crm::dashboard.filters.territory'))
                ->options(fn (): array => Lookup::options('territory'))
                ->placeholder(__('lite-crm::dashboard.filters.all_territories')),
            DatePicker::make('from')->label(__('lite-crm::dashboard.filters.from')),
            DatePicker::make('until')->label(__('lite-crm::dashboard.filters.until')),
        ]);
    }
}
