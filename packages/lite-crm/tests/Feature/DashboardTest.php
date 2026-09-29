<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use LiteCrm\Filament\Pages\CrmDashboard;
use LiteCrm\Filament\Resources\ExchangeRates\ExchangeRateResource;
use LiteCrm\Filament\Widgets\ActivityStreamWidget;
use LiteCrm\Filament\Widgets\EnquiriesWidget;
use LiteCrm\Filament\Widgets\ExpiringDocumentsWidget;
use LiteCrm\Filament\Widgets\MyDayWidget;
use LiteCrm\Filament\Widgets\NoticesWidget;
use LiteCrm\Filament\Widgets\PipelineWidget;
use LiteCrm\Filament\Widgets\PriceWatchWidget;
use LiteCrm\Filament\Widgets\SamplesTrialsWidget;
use LiteCrm\Filament\Widgets\TeamClockWidget;
use LiteCrm\Filament\Widgets\WonLostWidget;
use LiteCrm\Models\ExchangeRate;
use LiteCrm\Models\Opportunity;
use LiteCrm\Models\Pipeline;
use LiteCrm\Models\Task;
use LiteCrm\Support\CrmSettings;
use LiteCrm\Support\Money;
use LiteCrm\Tests\TestCase;
use Livewire\Livewire;

uses(TestCase::class);

beforeEach(function (): void {
    $this->seedCrm();
    Filament::setCurrentPanel(Filament::getPanel('crm'));
});

it('shows the dashboard with its widgets to every CRM role', function (string $role): void {
    $this->actingAs($this->withMfa($this->crmUser([$role])));

    $this->get(CrmDashboard::getUrl())->assertOk();
})->with(['admin', 'manager', 'commercial', 'specialist', 'partner', 'viewer']);

it('renders each widget without errors', function (string $widget): void {
    $this->actingAs($this->withMfa($this->crmUser(['admin'])));

    Livewire::test($widget)->assertOk();
})->with([
    MyDayWidget::class, EnquiriesWidget::class, PipelineWidget::class, WonLostWidget::class,
    ExpiringDocumentsWidget::class, PriceWatchWidget::class, SamplesTrialsWidget::class,
    TeamClockWidget::class, NoticesWidget::class, ActivityStreamWidget::class,
]);

it('lets hosts switch widgets off in config', function (): void {
    config(['lite-crm.dashboard_widgets.team_clock' => false, 'lite-crm.dashboard_widgets.price_watch' => false]);
    $this->actingAs($this->withMfa($this->crmUser(['admin'])));

    $widgets = (new CrmDashboard)->getWidgets();

    expect($widgets)->not->toContain(TeamClockWidget::class)
        ->not->toContain(PriceWatchWidget::class)
        ->toContain(MyDayWidget::class);
});

it('offers dashboard filters to managers but not to commercial users', function (): void {
    $this->actingAs($this->withMfa($this->crmUser(['manager'])));
    expect(CrmDashboard::canFilter())->toBeTrue();

    $this->actingAs($this->withMfa($this->crmUser(['commercial'])));
    expect(CrmDashboard::canFilter())->toBeFalse();
});

it('hides widgets for modules that are switched off', function (): void {
    config(['lite-crm.modules.opportunities' => false]);
    $this->actingAs($this->withMfa($this->crmUser(['admin'])));

    expect(PipelineWidget::canView())->toBeFalse()
        ->and(WonLostWidget::canView())->toBeFalse();
});

it('shows my open tasks on My day', function (): void {
    $user = $this->withMfa($this->crmUser(['commercial']));
    $this->actingAs($user);
    Task::query()->create(['title' => 'Call the mill', 'assignee_id' => $user->getKey(), 'due_at' => now()]);

    Livewire::test(MyDayWidget::class)->assertSee('Call the mill');
});

it('converts pipeline values into the base currency with the rate valid on the day', function (): void {
    app(CrmSettings::class)->set(CrmSettings::BASE_CURRENCY, 'USD');
    ExchangeRate::query()->create(['currency' => 'EUR', 'rate' => 1.10, 'valid_from' => now()->subMonth()->toDateString()]);
    ExchangeRate::query()->create(['currency' => 'EUR', 'rate' => 1.20, 'valid_from' => now()->toDateString()]);

    expect(Money::toBase(100, 'USD'))->toBe(100.0)
        ->and(Money::toBase(100, 'EUR'))->toBe(120.0)
        ->and(Money::toBase(100, 'EUR', now()->subWeek()))->toEqualWithDelta(110.0, 0.0001)
        ->and(Money::toBase(100, 'PKR'))->toBeNull();
});

it('warns on the pipeline widget when a rate is missing', function (): void {
    $user = $this->withMfa($this->crmUser(['admin']));
    $this->actingAs($user);
    $pipeline = Pipeline::query()->where('is_active', true)->firstOrFail();

    Opportunity::query()->create([
        'name' => 'Big order', 'pipeline_id' => $pipeline->getKey(), 'stage_id' => $pipeline->stages()->firstOrFail()->getKey(),
        'value' => 5000, 'currency' => 'PKR', 'owner_id' => $user->getKey(),
    ]);

    Livewire::test(PipelineWidget::class)->assertSee(__('lite-crm::dashboard.missing_rates'));
});

it('lets only users who manage settings maintain exchange rates', function (): void {
    $this->actingAs($this->withMfa($this->crmUser(['admin'])));
    expect(ExchangeRateResource::canViewAny())->toBeTrue();

    $this->actingAs($this->withMfa($this->crmUser(['commercial'])));
    expect(ExchangeRateResource::canViewAny())->toBeFalse();
});

it('warns on the pipeline widget when a rate in use is older than 30 days', function (): void {
    $user = $this->withMfa($this->crmUser(['admin']));
    $this->actingAs($user);
    $pipeline = Pipeline::query()->where('is_active', true)->firstOrFail();
    ExchangeRate::query()->create(['currency' => 'EUR', 'rate' => 1.1, 'valid_from' => now()->subDays(45)->toDateString()]);
    ExchangeRate::query()->create(['currency' => 'SGD', 'rate' => 0.75, 'valid_from' => now()->subDays(5)->toDateString()]);

    foreach (['EUR', 'SGD'] as $currency) {
        Opportunity::query()->create([
            'name' => 'Deal '.$currency, 'pipeline_id' => $pipeline->getKey(), 'stage_id' => $pipeline->stages()->firstOrFail()->getKey(),
            'value' => 1000, 'currency' => $currency, 'owner_id' => $user->getKey(),
        ]);
    }

    expect(Money::staleRates(['EUR', 'SGD', 'USD', 'PKR', null]))->toBe(['EUR' => now()->subDays(45)->toDateString()]);

    Livewire::test(PipelineWidget::class)
        ->assertSee(__('lite-crm::dashboard.stale_rates', ['days' => 30, 'rates' => 'EUR ('.now()->subDays(45)->format('j M Y').')']));

    config(['lite-crm.exchange_rates.stale_after_days' => 60]);
    expect(Money::staleRates(['EUR']))->toBe([]);
});
