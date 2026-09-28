<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use LiteCrm\Enums\TaskStatus;
use LiteCrm\Models\Activity;
use LiteCrm\Models\CustomField;
use LiteCrm\Models\Document;
use LiteCrm\Models\Lookup;
use LiteCrm\Models\Organisation;
use LiteCrm\Models\Task;
use LiteCrm\Notifications\TaskAssignedNotification;
use LiteCrm\Tests\TestCase;
use Spatie\Activitylog\Models\Activity as AuditEntry;

uses(TestCase::class);

beforeEach(fn () => $this->seedCrm());

function lookupId(string $type, string $key): int
{
    return (int) Lookup::query()->where('type', $type)->where('key', $key)->value('id');
}

it('records the owner, creator and last editor', function (): void {
    $alice = $this->crmUser(['commercial']);
    $bob = $this->crmUser(['commercial']);

    $this->actingAs($alice);
    $organisation = Organisation::query()->create(['name' => 'Acme Trading']);

    $this->actingAs($bob);
    $organisation->update(['city' => 'Lahore']);

    expect($organisation->refresh()->owner_id)->toBe($alice->id)
        ->and($organisation->getAttribute('created_by'))->toBe($alice->id)
        ->and($organisation->getAttribute('updated_by'))->toBe($bob->id)
        ->and($organisation->owner->is($alice))->toBeTrue();
});

it('soft-deletes records and writes every change to the audit log', function (): void {
    $this->actingAs($this->crmUser(['admin']));
    $organisation = Organisation::query()->create(['name' => 'Acme Trading']);
    $organisation->delete();

    expect(Organisation::query()->find($organisation->id))->toBeNull()
        ->and(Organisation::withTrashed()->find($organisation->id))->not->toBeNull()
        ->and($organisation->auditLog()->pluck('event')->all())->toContain('created', 'deleted');
});

it('stores morph types under stable aliases', function (): void {
    $this->actingAs($this->crmUser(['commercial']));
    $organisation = Organisation::query()->create(['name' => 'Acme Trading']);
    $task = $organisation->tasks()->create(['title' => 'Call back']);

    expect($task->taskable_type)->toBe('crm_organisation')
        ->and($task->taskable->is($organisation))->toBeTrue()
        ->and(AuditEntry::query()->where('subject_type', 'crm_organisation')->where('subject_id', $organisation->id)->exists())->toBeTrue()
        ->and(AuditEntry::query()->where('subject_type', Organisation::class)->exists())->toBeFalse();
});

it('applies organisation custom fields according to the organisation type', function (): void {
    $this->actingAs($this->crmUser(['commercial']));

    CustomField::query()->create([
        'entity' => 'organisation', 'key' => 'account_code', 'label' => 'Account code', 'type' => 'text',
        'required' => true, 'visible_for_types' => ['customer'],
    ]);

    expect(fn () => Organisation::query()->create(['name' => 'Customer Ltd', 'type_id' => lookupId('organisation_type', 'customer')]))
        ->toThrow(ValidationException::class);

    $supplier = Organisation::query()->create(['name' => 'Supplier Ltd', 'type_id' => lookupId('organisation_type', 'supplier')]);
    $customer = Organisation::query()->create([
        'name' => 'Customer Ltd', 'type_id' => lookupId('organisation_type', 'customer'), 'custom' => ['account_code' => 'C-001'],
    ]);

    expect($supplier->exists)->toBeTrue()
        ->and($customer->getCustomValue('account_code'))->toBe('C-001');
});

it('requires a recorded date before an organisation may be named publicly', function (): void {
    $organisation = new Organisation(['name' => 'Acme', 'permission_to_name_publicly' => true]);

    expect($organisation->mayBeNamedPublicly())->toBeFalse();

    $organisation->permission_granted_on = now();

    expect($organisation->mayBeNamedPublicly())->toBeTrue();
});

it('links contacts to organisations and builds their full name', function (): void {
    $this->actingAs($this->crmUser(['commercial']));
    $organisation = Organisation::query()->create(['name' => 'Acme Trading']);
    $contact = $organisation->contacts()->create(['first_name' => 'Sara', 'last_name' => 'Khan', 'languages' => ['English', 'Urdu']]);

    expect($contact->name)->toBe('Sara Khan')
        ->and($contact->organisation->is($organisation))->toBeTrue()
        ->and($contact->refresh()->languages)->toBe(['English', 'Urdu']);
});

it('records activities with team and contact participants', function (): void {
    $user = $this->crmUser(['commercial']);
    $this->actingAs($user);
    $organisation = Organisation::query()->create(['name' => 'Acme Trading']);
    $contact = $organisation->contacts()->create(['last_name' => 'Khan']);

    /** @var Activity $activity */
    $activity = $organisation->activities()->create([
        'type_id' => lookupId('activity_type', 'meeting'),
        'occurred_at' => now(),
        'summary' => 'Discussed requirements',
    ]);
    $activity->participantUsers()->attach($user);
    $activity->participantContacts()->attach($contact);

    expect($organisation->activities()->count())->toBe(1)
        ->and($activity->participantUsers()->pluck('id')->all())->toBe([$user->id])
        ->and($activity->participantContacts()->pluck('id')->all())->toBe([$contact->id]);
});

it('stamps completion and knows when a task is overdue', function (): void {
    $this->actingAs($this->crmUser(['commercial']));

    $late = Task::query()->create(['title' => 'Late', 'due_at' => now()->subDay()]);
    $future = Task::query()->create(['title' => 'Later', 'due_at' => now()->addDay()]);

    expect($late->isOverdue())->toBeTrue()
        ->and($future->isOverdue())->toBeFalse()
        ->and(Task::query()->overdue()->pluck('title')->all())->toBe(['Late']);

    $late->markDone();

    expect($late->status)->toBe(TaskStatus::Done)
        ->and($late->completed_at)->not->toBeNull()
        ->and($late->isOverdue())->toBeFalse()
        ->and(Task::query()->overdue()->count())->toBe(0);
});

it('e-mails the assignee, but not someone who assigns a task to themselves', function (): void {
    Notification::fake();
    $manager = $this->crmUser(['manager']);
    $colleague = $this->crmUser(['commercial']);
    $this->actingAs($manager);

    $task = Task::query()->create(['title' => 'Send the specification', 'assignee_id' => $colleague->id]);
    Task::query()->create(['title' => 'My own task', 'assignee_id' => $manager->id]);

    Notification::assertSentTo($colleague, TaskAssignedNotification::class);
    Notification::assertNotSentTo($manager, TaskAssignedNotification::class);

    $other = $this->crmUser(['commercial']);
    $task->update(['assignee_id' => $other->id]);

    Notification::assertSentTo($other, TaskAssignedNotification::class);
});

it('works out which documents are expiring or expired', function (): void {
    $this->actingAs($this->crmUser(['commercial']));

    $make = fn (string $title, ?string $expires) => Document::query()->create([
        'title' => $title, 'file_path' => 'crm/documents/'.$title.'.pdf', 'file_name' => $title.'.pdf', 'expires_on' => $expires,
    ]);

    $soon = $make('soon', now()->addDays(30)->toDateString());
    $later = $make('later', now()->addDays(200)->toDateString());
    $expired = $make('expired', now()->subDays(3)->toDateString());

    expect($soon->expiresWithin(60))->toBeTrue()
        ->and($later->expiresWithin(60))->toBeFalse()
        ->and($expired->isExpired())->toBeTrue()
        ->and(Document::query()->expiringWithin(60)->pluck('title')->all())->toBe(['soon'])
        ->and($soon->disk)->toBe('local');
});
