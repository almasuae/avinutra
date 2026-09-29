<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use LiteCrm\Enquiries\EnquiryConverter;
use LiteCrm\Events\OpportunityLost;
use LiteCrm\Events\OpportunityStageChanged;
use LiteCrm\Events\OpportunityWon;
use LiteCrm\Filament\Pages\OpportunityBoard;
use LiteCrm\Filament\Resources\Opportunities\Pages\ListOpportunities;
use LiteCrm\Filament\Resources\Opportunities\Pages\ViewOpportunity;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Lookup;
use LiteCrm\Models\Opportunity;
use LiteCrm\Models\Organisation;
use LiteCrm\Models\Pipeline;
use LiteCrm\Models\PipelineStage;
use LiteCrm\Tests\Fixtures\User;
use LiteCrm\Tests\TestCase;
use Livewire\Livewire;

uses(TestCase::class);

beforeEach(function (): void {
    $this->seedCrm();
    Filament::setCurrentPanel(Filament::getPanel('crm'));
    $this->sales = Pipeline::query()->where('key', 'sales')->firstOrFail();
});

function stage(Pipeline $pipeline, string $key): PipelineStage
{
    return $pipeline->stages()->where('key', $key)->firstOrFail();
}

function member(TestCase $test, array $roles, array $profile = []): User
{
    $user = $test->crmUser($roles, $profile);
    $test->withMfa($user);
    $test->actingAs($user->refresh());

    return $user;
}

it('starts in the first stage with that stage\'s probability', function (): void {
    $opportunity = Opportunity::query()->create(['name' => 'Big order', 'pipeline_id' => $this->sales->id, 'value' => 10000, 'currency' => 'USD']);

    expect($opportunity->stage->key)->toBe('new')
        ->and($opportunity->probability)->toBe(10)
        ->and($opportunity->weightedValue())->toBe(1000.0)
        ->and($opportunity->isOpen())->toBeTrue();
});

it('logs every stage change and fires the stage, won and lost events', function (): void {
    Event::fake([OpportunityStageChanged::class, OpportunityWon::class, OpportunityLost::class]);
    $this->actingAs($this->crmUser(['commercial']));
    $opportunity = Opportunity::query()->create(['name' => 'Deal', 'pipeline_id' => $this->sales->id]);

    $opportunity->update(['stage_id' => stage($this->sales, 'proposal')->id]);

    expect($opportunity->refresh()->probability)->toBe(50)
        ->and($opportunity->activities()->sole()->summary)->toBe('Stage changed: New → Proposal')
        ->and($opportunity->activities()->sole()->type->key)->toBe('stage_change');

    $opportunity->update(['stage_id' => stage($this->sales, 'won')->id]);

    expect($opportunity->refresh()->closed_at)->not->toBeNull();
    Event::assertDispatched(OpportunityWon::class);
    Event::assertDispatchedTimes(OpportunityStageChanged::class, 3);

    // Reopening clears the close date.
    $opportunity->update(['stage_id' => stage($this->sales, 'negotiation')->id]);
    expect($opportunity->refresh()->closed_at)->toBeNull();

    $opportunity->update(['stage_id' => stage($this->sales, 'lost')->id, 'lost_reason_id' => Lookup::query()->where('key', 'price')->value('id')]);
    Event::assertDispatched(OpportunityLost::class);
    expect($opportunity->refresh()->lostReason->key)->toBe('price');
});

it('keeps a probability set by hand in the same save', function (): void {
    $opportunity = Opportunity::query()->create(['name' => 'Deal', 'pipeline_id' => $this->sales->id]);
    $opportunity->update(['stage_id' => stage($this->sales, 'proposal')->id, 'probability' => 65]);

    expect($opportunity->refresh()->probability)->toBe(65);
});

it('refuses a stage from another pipeline', function (): void {
    $other = Pipeline::query()->create(['key' => 'other', 'name' => 'Other']);
    $foreign = $other->stages()->create(['key' => 'x', 'name' => 'X']);

    expect(fn () => Opportunity::query()->create(['name' => 'Deal', 'pipeline_id' => $this->sales->id, 'stage_id' => $foreign->id]))
        ->toThrow(ValidationException::class);
});

it('hides pipelines restricted to other roles, except from admins', function (): void {
    $suppliers = Pipeline::query()->create(['key' => 'partners', 'name' => 'Partnerships', 'visible_to_roles' => ['manager']]);
    $suppliers->stages()->create(['key' => 'identified', 'name' => 'Identified']);
    $commercial = $this->crmUser(['commercial']);
    $manager = $this->crmUser(['manager']);
    $admin = $this->crmUser(['admin']);
    $deal = Opportunity::query()->create(['name' => 'Supply deal', 'pipeline_id' => $suppliers->id]);

    expect($suppliers->isVisibleTo($commercial))->toBeFalse()
        ->and($suppliers->isVisibleTo($manager))->toBeTrue()
        ->and($suppliers->isVisibleTo($admin))->toBeTrue()
        ->and($deal->isVisibleTo($commercial))->toBeFalse()
        ->and($deal->isVisibleTo($manager))->toBeTrue();
});

it('lists opportunities with the open filter by default', function (): void {
    member($this, ['commercial']);
    $open = Opportunity::query()->create(['name' => 'Open', 'pipeline_id' => $this->sales->id]);
    $won = Opportunity::query()->create(['name' => 'Won', 'pipeline_id' => $this->sales->id]);
    $won->update(['stage_id' => stage($this->sales, 'won')->id]);

    Livewire::test(ListOpportunities::class)
        ->assertCanSeeTableRecords([$open])
        ->assertCanNotSeeTableRecords([$won]);
});

it('moves cards on the board and asks for a reason before "lost"', function (): void {
    member($this, ['commercial']);
    $opportunity = Opportunity::query()->create(['name' => 'Deal', 'pipeline_id' => $this->sales->id]);

    Livewire::test(OpportunityBoard::class)
        ->assertSee('Deal')
        ->call('moveOpportunity', $opportunity->id, stage($this->sales, 'qualified')->id);

    expect($opportunity->refresh()->stage->key)->toBe('qualified');

    Livewire::test(OpportunityBoard::class)
        ->call('moveOpportunity', $opportunity->id, stage($this->sales, 'lost')->id)
        ->assertActionMounted('markLost')
        ->setActionData(['lost_reason_id' => Lookup::query()->where('key', 'timing')->value('id')])
        ->callMountedAction()
        ->assertHasNoActionErrors();

    expect($opportunity->refresh()->stage->key)->toBe('lost')
        ->and($opportunity->lostReason->key)->toBe('timing');
});

it('does not let users move opportunities they cannot see or change', function (): void {
    $this->actingAs($this->crmUser(['commercial']));
    $opportunity = Opportunity::query()->create(['name' => 'Someone else\'s', 'pipeline_id' => $this->sales->id]);

    member($this, ['viewer']);

    Livewire::test(OpportunityBoard::class)->call('moveOpportunity', $opportunity->id, stage($this->sales, 'won')->id);

    expect($opportunity->refresh()->stage->key)->toBe('new');
});

it('marks an opportunity won or lost from its page', function (): void {
    member($this, ['commercial']);
    $won = Opportunity::query()->create(['name' => 'Won deal', 'pipeline_id' => $this->sales->id]);
    $lost = Opportunity::query()->create(['name' => 'Lost deal', 'pipeline_id' => $this->sales->id]);

    Livewire::test(ViewOpportunity::class, ['record' => $won->getRouteKey()])->callAction('markWon');
    Livewire::test(ViewOpportunity::class, ['record' => $lost->getRouteKey()])
        ->callAction('markLost', data: ['lost_reason_id' => Lookup::query()->where('key', 'competitor')->value('id')])
        ->assertHasNoActionErrors();

    expect($won->refresh()->stage->is_won)->toBeTrue()
        ->and($lost->refresh()->stage->is_lost)->toBeTrue()
        ->and($lost->lostReason->key)->toBe('competitor');
});

it('opens an opportunity when an enquiry is converted', function (): void {
    $user = $this->crmUser(['commercial']);
    $this->actingAs($user);
    $enquiry = LiteCrm::captureEnquiry(['name' => 'Sara Khan', 'company' => 'Example Trading', 'email' => 'sara@example.com'], 'general');

    app(EnquiryConverter::class)->convert($enquiry, [
        'organisation_action' => EnquiryConverter::NEW,
        'organisation' => ['name' => 'Example Trading'],
        'opportunity' => ['create' => true, 'pipeline_id' => $this->sales->id, 'name' => 'First order'],
    ], $user);

    $opportunity = Opportunity::query()->sole();

    expect($opportunity->name)->toBe('First order')
        ->and($opportunity->enquiry_id)->toBe($enquiry->id)
        ->and($opportunity->organisation_id)->toBe(Organisation::query()->sole()->id)
        ->and($opportunity->owner_id)->toBe($user->id);
});
