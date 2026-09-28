<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Gate;
use LiteCrm\Filament\Resources\Organisations\OrganisationResource;
use LiteCrm\Models\Activity;
use LiteCrm\Models\Contact;
use LiteCrm\Models\Document;
use LiteCrm\Models\Lookup;
use LiteCrm\Models\Organisation;
use LiteCrm\Models\Task;
use LiteCrm\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    $this->seedCrm();

    $this->north = Lookup::query()->create(['type' => 'territory', 'key' => 'north', 'label' => 'North'])->id;
    $this->south = Lookup::query()->create(['type' => 'territory', 'key' => 'south', 'label' => 'South'])->id;

    $this->partner = $this->crmUser(['partner'], ['territory_id' => $this->north]);
    $this->commercial = $this->crmUser(['commercial']);

    // Created by the commercial user, so the partner owns none of them.
    $this->actingAs($this->commercial);
    $this->inTerritory = Organisation::query()->create(['name' => 'North Ltd', 'territory_id' => $this->north]);
    $this->elsewhere = Organisation::query()->create(['name' => 'South Ltd', 'territory_id' => $this->south]);
    $this->noTerritory = Organisation::query()->create(['name' => 'Nowhere Ltd']);
    $this->ownByPartner = Organisation::query()->create(['name' => 'Partner Own Ltd', 'owner_id' => $this->partner->id]);
});

it('shows partners only their own organisations and those in their territory', function (): void {
    expect(Organisation::query()->visibleTo($this->partner)->orderBy('name')->pluck('name')->all())
        ->toBe(['North Ltd', 'Partner Own Ltd'])
        ->and(Organisation::query()->visibleTo($this->commercial)->count())->toBe(4);
});

it('shows partners contacts of visible organisations only', function (): void {
    $this->inTerritory->contacts()->create(['last_name' => 'Visible']);
    $this->elsewhere->contacts()->create(['last_name' => 'Hidden']);
    Contact::query()->create(['last_name' => 'Own', 'owner_id' => $this->partner->id]);

    expect(Contact::query()->visibleTo($this->partner)->orderBy('last_name')->pluck('last_name')->all())->toBe(['Own', 'Visible']);
});

it('shows partners tasks assigned to them and tasks on visible records', function (): void {
    $this->inTerritory->tasks()->create(['title' => 'On visible organisation']);
    $this->elsewhere->tasks()->create(['title' => 'On hidden organisation']);
    $this->elsewhere->tasks()->create(['title' => 'Assigned to partner', 'assignee_id' => $this->partner->id]);

    expect(Task::query()->visibleTo($this->partner)->orderBy('title')->pluck('title')->all())
        ->toBe(['Assigned to partner', 'On visible organisation']);
});

it('shows partners activities on visible records and those they took part in', function (): void {
    $base = ['occurred_at' => now(), 'summary' => 'x'];
    $this->inTerritory->activities()->create(['summary' => 'On visible'] + $base);
    $this->elsewhere->activities()->create(['summary' => 'On hidden'] + $base);
    /** @var Activity $joined */
    $joined = $this->elsewhere->activities()->create(['summary' => 'Took part'] + $base);
    $joined->participantUsers()->attach($this->partner);

    expect(Activity::query()->visibleTo($this->partner)->orderBy('summary')->pluck('summary')->all())
        ->toBe(['On visible', 'Took part']);
});

it('lets partners change only what they can see', function (): void {
    expect(Gate::forUser($this->partner)->allows('update', $this->inTerritory))->toBeTrue()
        ->and(Gate::forUser($this->partner)->allows('view', $this->elsewhere))->toBeFalse()
        ->and(Gate::forUser($this->partner)->allows('update', $this->elsewhere))->toBeFalse()
        ->and(Gate::forUser($this->commercial)->allows('update', $this->elsewhere))->toBeTrue();
});

it('lets only admins delete, and nobody hard-delete', function (): void {
    $admin = $this->crmUser(['admin']);

    expect(Gate::forUser($this->commercial)->allows('delete', $this->inTerritory))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('delete', $this->inTerritory))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('restore', $this->inTerritory))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('forceDelete', $this->inTerritory))->toBeFalse();
});

it('keeps confidential documents to their owner and those allowed to see them', function (): void {
    $document = $this->inTerritory->documents()->create([
        'title' => 'Agreement', 'file_path' => 'crm/documents/a.pdf', 'file_name' => 'a.pdf', 'confidential' => true,
    ]);
    $public = $this->inTerritory->documents()->create([
        'title' => 'Brochure', 'file_path' => 'crm/documents/b.pdf', 'file_name' => 'b.pdf',
    ]);
    $otherCommercial = $this->crmUser(['commercial']);
    $manager = $this->crmUser(['manager']);

    expect($document->isVisibleTo($this->commercial))->toBeTrue()   // owner
        ->and($document->isVisibleTo($otherCommercial))->toBeFalse() // view_all, but not confidential
        ->and($document->isVisibleTo($manager))->toBeTrue()          // documents.view_confidential
        ->and($document->isVisibleTo($this->partner))->toBeFalse()
        ->and($public->isVisibleTo($this->partner))->toBeTrue()     // on a visible organisation
        ->and(Document::query()->visibleTo($otherCommercial)->pluck('title')->all())->toBe(['Brochure']);
});

it('hides a module completely when it is switched off', function (): void {
    config(['lite-crm.modules.organisations' => false]);

    $this->actingAs($this->commercial);

    expect(Gate::forUser($this->commercial)->allows('viewAny', Organisation::class))->toBeFalse()
        ->and(OrganisationResource::canAccess())->toBeFalse();
});

it('shows nothing to users without a CRM role', function (): void {
    $stranger = $this->crmUser([]);

    expect(Organisation::query()->visibleTo($stranger)->count())->toBe(0)
        ->and(Organisation::query()->visibleTo(null)->count())->toBe(0);
});
