<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Gate;
use LiteCrm\Enums\PriceBasis;
use LiteCrm\Enums\PriceSourceType;
use LiteCrm\Filament\Resources\PriceLog\PriceEntryResource;
use LiteCrm\Filament\Resources\Quotations\Pages\CreateQuotation;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Announcement;
use LiteCrm\Models\Decision;
use LiteCrm\Models\Lookup;
use LiteCrm\Models\Organisation;
use LiteCrm\Models\PriceEntry;
use LiteCrm\Models\Quotation;
use LiteCrm\Models\Sample;
use LiteCrm\Models\Trial;
use LiteCrm\Tests\TestCase;
use Livewire\Livewire;

uses(TestCase::class);

beforeEach(function (): void {
    $this->seedCrm();
    Filament::setCurrentPanel(Filament::getPanel('crm'));
});

afterEach(fn () => LiteCrm::resolveContractingEntityUsing(null));

it('tracks where a sample is', function (): void {
    $sample = Sample::query()->create(['lot_number' => 'L-1']);
    expect($sample->stage())->toBe(Sample::PREPARING);

    $sample->update(['sent_on' => now()->subDays(3)]);
    expect($sample->stage())->toBe(Sample::SENT);

    $sample->update(['received_on' => now()]);
    expect($sample->stage())->toBe(Sample::RECEIVED)
        ->and(Sample::query()->inProgress()->count())->toBe(1);

    $sample->update(['feedback' => 'Good flow']);
    expect($sample->stage())->toBe(Sample::FEEDBACK)
        ->and(Sample::query()->inProgress()->count())->toBe(0);
});

it('allows trial results to be published only with dated consent', function (): void {
    $trial = Trial::query()->create(['name' => 'Field trial']);
    expect($trial->mayBePublished())->toBeFalse();

    $trial->update(['consent_to_publish' => true]);
    expect($trial->refresh()->mayBePublished())->toBeFalse();

    $trial->update(['consent_date' => now()]);
    expect($trial->refresh()->mayBePublished())->toBeTrue();
});

it('numbers quotations per year without gaps or reuse', function (): void {
    $this->travelTo(now()->setDate(2026, 5, 1));

    $first = Quotation::query()->create([]);
    $second = Quotation::query()->create([]);
    $second->delete();
    $third = Quotation::query()->create([]);

    $this->travelTo(now()->setDate(2027, 1, 2));
    $nextYear = Quotation::query()->create([]);

    expect([$first->number, $second->number, $third->number, $nextYear->number])
        ->toBe(['Q-2026-0001', 'Q-2026-0002', 'Q-2026-0003', 'Q-2027-0001']);
});

it('uses the configured prefix and the host\'s contracting entity', function (): void {
    config(['lite-crm.quotations.number_prefix' => 'ACME-Q', 'lite-crm.quotations.contracting_entity' => 'Fallback Ltd']);

    expect(Quotation::query()->create([])->contracting_entity)->toBe('Fallback Ltd');

    LiteCrm::resolveContractingEntityUsing(fn (): string => 'Host Resolver Ltd');
    $quotation = Quotation::query()->create(['quantity' => 20, 'price' => 2.5]);

    expect($quotation->number)->toStartWith('ACME-Q-')
        ->and($quotation->contracting_entity)->toBe('Host Resolver Ltd')
        ->and($quotation->refresh()->total())->toBe(50.0);
});

it('creates a quotation from the form with its number and validity', function (): void {
    $user = $this->crmUser(['commercial']);
    $this->withMfa($user);
    $this->actingAs($user->refresh());
    $organisation = Organisation::query()->create(['name' => 'Acme']);

    Livewire::test(CreateQuotation::class)
        ->assertSchemaStateSet(['valid_until' => now()->addDays(30)->toDateString()])
        ->fillForm(['organisation_id' => $organisation->id, 'quantity' => 1000, 'unit' => 'kg', 'price' => 2.8, 'currency' => 'USD', 'incoterm' => 'CFR', 'port' => 'Port A'])
        ->call('create')
        ->assertHasNoFormErrors();

    $quotation = Quotation::query()->sole();

    expect($quotation->number)->toMatch('/^Q-\d{4}-0001$/')
        ->and($quotation->incoterm->value)->toBe('CFR')
        ->and($quotation->owner_id)->toBe($user->id);
});

it('keeps the price log away from partners', function (): void {
    $partner = $this->crmUser(['partner']);
    $commercial = $this->crmUser(['commercial']);

    $this->actingAs($partner);
    expect(PriceEntryResource::canAccess())->toBeFalse();

    $this->actingAs($commercial);
    $entry = PriceEntry::query()->create([
        'observed_on' => now(), 'source_type' => PriceSourceType::SupplierOffer, 'basis' => PriceBasis::Cfr,
        'price' => 2.8, 'currency' => 'USD', 'unit' => 'kg',
    ]);

    expect(PriceEntryResource::canAccess())->toBeTrue()
        ->and(Gate::forUser($partner)->allows('view', $entry))->toBeFalse()
        ->and($entry->refresh()->basis)->toBe(PriceBasis::Cfr);
});

it('shows announcements only to their audience, and renders Markdown safely', function (): void {
    $viewer = $this->crmUser(['viewer']);
    $manager = $this->crmUser(['manager']);
    $this->actingAs($manager);

    Announcement::query()->create(['title' => 'All hands', 'body' => 'For **everyone**']);
    $private = Announcement::query()->create(['title' => 'Managers only', 'body' => 'x', 'audience_roles' => ['manager']]);
    $unsafe = Announcement::query()->create(['title' => 'Unsafe', 'body' => "<script>alert(1)</script>\n\n[link](javascript:alert(1))"]);

    expect(Announcement::query()->visibleTo($viewer)->pluck('title')->all())->not->toContain('Managers only')
        ->and(Announcement::query()->visibleTo($manager)->pluck('title')->all())->toContain('Managers only')
        ->and($private->isVisibleTo($viewer))->toBeFalse()
        ->and((string) $unsafe->bodyHtml())->not->toContain('<script>')->not->toContain('javascript:');
});

it('keeps decisions append-only for everyone but admins', function (): void {
    $commercial = $this->crmUser(['commercial']);
    $admin = $this->crmUser(['admin']);
    $this->actingAs($commercial);

    $decision = Decision::query()->create(['decided_on' => now(), 'title' => 'Use supplier B', 'decision' => 'Approved']);

    expect(Gate::forUser($commercial)->allows('create', Decision::class))->toBeTrue()
        ->and(Gate::forUser($commercial)->allows('view', $decision))->toBeTrue()
        ->and(Gate::forUser($commercial)->allows('update', $decision))->toBeFalse()
        ->and(Gate::forUser($commercial)->allows('delete', $decision))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('update', $decision))->toBeTrue();
});

it('opens every new record screen', function (string $path): void {
    $admin = $this->crmUser(['admin']);
    $this->withMfa($admin);
    $this->actingAs($admin->refresh());

    $this->get('/crm/'.$path)->assertOk();
})->with([
    'products', 'products/create', 'opportunities', 'opportunities/create', 'opportunity-board',
    'samples', 'trials', 'trials/create', 'quotations', 'quotations/create', 'price-log', 'announcements', 'decisions',
]);

it('adds new lookups for stage changes without disturbing existing ones', function (): void {
    expect(Lookup::query()->where('type', 'activity_type')->where('key', 'stage_change')->value('label'))->toBe('Stage change');
});
