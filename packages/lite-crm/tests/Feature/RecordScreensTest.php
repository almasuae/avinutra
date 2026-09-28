<?php

declare(strict_types=1);

use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use LiteCrm\Enums\TaskStatus;
use LiteCrm\Filament\Resources\Contacts\Pages\ListContacts;
use LiteCrm\Filament\Resources\Documents\Pages\ManageDocuments;
use LiteCrm\Filament\Resources\Organisations\Pages\CreateOrganisation;
use LiteCrm\Filament\Resources\Organisations\Pages\ListOrganisations;
use LiteCrm\Filament\Resources\Organisations\Pages\ViewOrganisation;
use LiteCrm\Filament\Resources\Organisations\RelationManagers\ContactsRelationManager;
use LiteCrm\Filament\Resources\Shared\ActivitiesRelationManager;
use LiteCrm\Filament\Resources\Tasks\Pages\ManageTasks;
use LiteCrm\Models\CustomField;
use LiteCrm\Models\Document;
use LiteCrm\Models\Lookup;
use LiteCrm\Models\Organisation;
use LiteCrm\Models\Task;
use LiteCrm\Tests\Fixtures\User;
use LiteCrm\Tests\TestCase;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity as AuditEntry;

uses(TestCase::class);

beforeEach(function (): void {
    $this->seedCrm();
    Filament::setCurrentPanel(Filament::getPanel('crm'));
    Storage::fake('local');
});

function actAs(TestCase $test, array $roles, array $profile = []): User
{
    $user = $test->crmUser($roles, $profile);
    $test->withMfa($user);
    $test->actingAs($user->refresh());

    return $user;
}

function typeId(string $type, string $key): int
{
    return (int) Lookup::query()->where('type', $type)->where('key', $key)->value('id');
}

it('opens every record screen', function (string $path): void {
    actAs($this, ['admin']);

    $this->get('/crm/'.$path)->assertOk();
})->with(['organisations', 'organisations/create', 'contacts', 'contacts/create', 'activities', 'tasks', 'documents']);

it('lists only the organisations a partner may see', function (): void {
    $north = Lookup::query()->create(['type' => 'territory', 'key' => 'north', 'label' => 'North'])->id;
    $colleague = $this->crmUser(['commercial']);
    $this->actingAs($colleague);
    $visible = Organisation::query()->create(['name' => 'North Ltd', 'territory_id' => $north]);
    $hidden = Organisation::query()->create(['name' => 'Elsewhere Ltd']);

    actAs($this, ['partner'], ['territory_id' => $north]);

    Livewire::test(ListOrganisations::class)
        ->assertCanSeeTableRecords([$visible])
        ->assertCanNotSeeTableRecords([$hidden]);

    $this->get('/crm/organisations/'.$hidden->id)->assertNotFound();
    $this->get('/crm/organisations/'.$visible->id)->assertOk();
});

it('creates an organisation with custom fields from the form', function (): void {
    CustomField::query()->create([
        'entity' => 'organisation', 'key' => 'employees', 'label' => 'Employees', 'type' => 'number',
        'visible_for_types' => ['customer'],
    ]);
    $user = actAs($this, ['commercial']);

    Livewire::test(CreateOrganisation::class)
        ->fillForm([
            'name' => 'Acme Trading',
            'type_id' => typeId('organisation_type', 'customer'),
            'city' => 'Karachi',
            'custom' => ['employees' => '120'],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $organisation = Organisation::query()->where('name', 'Acme Trading')->firstOrFail();

    expect($organisation->custom)->toBe(['employees' => 120])
        ->and($organisation->owner_id)->toBe($user->id);
});

it('does not let viewers create records', function (): void {
    actAs($this, ['viewer']);

    $this->get('/crm/organisations/create')->assertForbidden();
    Livewire::test(ListOrganisations::class)->assertActionHidden('create');
});

it('logs an activity from the record page', function (): void {
    $user = actAs($this, ['commercial']);
    $organisation = Organisation::query()->create(['name' => 'Acme Trading']);
    $contact = $organisation->contacts()->create(['last_name' => 'Khan']);

    Livewire::test(ViewOrganisation::class, ['record' => $organisation->getRouteKey()])
        ->callAction('logActivity', data: [
            'type_id' => typeId('activity_type', 'call'),
            'occurred_at' => now()->format('Y-m-d H:i'),
            'summary' => 'Agreed to send samples',
            'participantUsers' => [$user->id],
            'participantContacts' => [$contact->id],
        ])
        ->assertHasNoActionErrors();

    $activity = $organisation->activities()->firstOrFail();

    expect($activity->summary)->toBe('Agreed to send samples')
        ->and($activity->owner_id)->toBe($user->id)
        ->and($activity->participantContacts()->pluck('id')->all())->toBe([$contact->id]);
});

it('shows only the record\'s own activities in its relation manager', function (): void {
    actAs($this, ['commercial']);
    $mine = Organisation::query()->create(['name' => 'Acme']);
    $other = Organisation::query()->create(['name' => 'Other']);
    $own = $mine->activities()->create(['occurred_at' => now(), 'summary' => 'Own']);
    $foreign = $other->activities()->create(['occurred_at' => now(), 'summary' => 'Foreign']);

    Livewire::test(ActivitiesRelationManager::class, ['ownerRecord' => $mine, 'pageClass' => ViewOrganisation::class])
        ->assertCanSeeTableRecords([$own])
        ->assertCanNotSeeTableRecords([$foreign]);
});

it('adds a contact from the organisation page', function (): void {
    actAs($this, ['commercial']);
    $organisation = Organisation::query()->create(['name' => 'Acme']);

    Livewire::test(ContactsRelationManager::class, ['ownerRecord' => $organisation, 'pageClass' => ViewOrganisation::class])
        ->callAction(TestAction::make('create')->table(), data: ['first_name' => 'Sara', 'last_name' => 'Khan', 'email' => 'sara@example.com'])
        ->assertHasNoActionErrors();

    expect($organisation->contacts()->firstOrFail()->name)->toBe('Sara Khan');
});

it('searches contacts by name', function (): void {
    actAs($this, ['commercial']);
    $organisation = Organisation::query()->create(['name' => 'Acme']);
    $sara = $organisation->contacts()->create(['first_name' => 'Sara', 'last_name' => 'Khan']);
    $omar = $organisation->contacts()->create(['first_name' => 'Omar', 'last_name' => 'Ali']);

    Livewire::test(ListContacts::class)
        ->searchTable('Sara')
        ->assertCanSeeTableRecords([$sara])
        ->assertCanNotSeeTableRecords([$omar]);
});

it('shows my tasks, the team\'s and overdue tasks in separate tabs, and completes them', function (): void {
    $me = actAs($this, ['commercial']);
    $colleague = $this->crmUser(['commercial']);
    $mine = Task::query()->create(['title' => 'Mine', 'assignee_id' => $me->id, 'due_at' => now()->addDay()]);
    $theirs = Task::query()->create(['title' => 'Theirs', 'assignee_id' => $colleague->id, 'due_at' => now()->subDay()]);

    Livewire::test(ManageTasks::class)
        ->assertCanSeeTableRecords([$mine])
        ->assertCanNotSeeTableRecords([$theirs])
        ->set('activeTab', 'team')
        ->assertCanSeeTableRecords([$mine, $theirs])
        ->set('activeTab', 'overdue')
        ->assertCanSeeTableRecords([$theirs])
        ->assertCanNotSeeTableRecords([$mine])
        ->set('activeTab', 'mine')
        ->callAction(TestAction::make('complete')->table($mine));

    expect($mine->refresh()->status)->toBe(TaskStatus::Done);
});

it('stores uploaded documents on the private disk', function (): void {
    actAs($this, ['commercial']);
    $organisation = Organisation::query()->create(['name' => 'Acme']);

    Livewire::test(ManageDocuments::class)
        ->callAction('create', data: [
            'title' => 'Quality certificate',
            'type_id' => typeId('document_type', 'certificate'),
            'file_path' => UploadedFile::fake()->create('certificate.pdf', 200, 'application/pdf'),
            'documentable_type' => 'crm_organisation',
            'documentable_id' => $organisation->id,
            'expires_on' => now()->addYear()->toDateString(),
        ])
        ->assertHasNoActionErrors();

    $document = Document::query()->firstOrFail();

    expect($document->file_path)->toStartWith('crm/documents/')
        ->and($document->file_name)->toBe('certificate.pdf')
        ->and($document->disk)->toBe('local')
        ->and($document->mime_type)->toBe('application/pdf')
        ->and($document->documentable->is($organisation))->toBeTrue();

    Storage::disk('local')->assertExists($document->file_path);
    Storage::disk('public')->assertMissing($document->file_path);
});

it('refuses files of other types', function (): void {
    actAs($this, ['commercial']);

    Livewire::test(ManageDocuments::class)
        ->callAction('create', data: [
            'title' => 'Program',
            'file_path' => UploadedFile::fake()->create('setup.exe', 10, 'application/x-msdownload'),
        ])
        ->assertHasActionErrors(['file_path']);

    expect(Document::query()->count())->toBe(0);
});

it('serves documents only through a valid signed link, to users allowed to see them', function (): void {
    $owner = actAs($this, ['commercial']);
    Storage::disk('local')->put('crm/documents/spec.pdf', '%PDF-1.4 test');
    $document = Document::query()->create(['title' => 'Spec', 'file_path' => 'crm/documents/spec.pdf', 'file_name' => 'spec.pdf', 'confidential' => true]);

    $signed = $document->temporaryDownloadUrl();
    $unsigned = strtok($signed, '?');

    // Signed and allowed.
    $this->get($signed)->assertOk()->assertDownload('spec.pdf');
    expect(AuditEntry::query()->where('event', 'downloaded')->where('causer_id', $owner->id)->exists())->toBeTrue();

    // No signature.
    $this->get($unsigned)->assertForbidden();

    // Signed, but the user may not see a confidential document.
    actAs($this, ['commercial']);
    $this->get($signed)->assertForbidden();

    // Signed, but expired.
    $this->actingAs($owner);
    $this->travel(6)->minutes();
    $this->get($signed)->assertForbidden();
});

it('requires login for document downloads', function (): void {
    $owner = $this->crmUser(['commercial']);
    $this->actingAs($owner);
    Storage::disk('local')->put('crm/documents/spec.pdf', 'x');
    $document = Document::query()->create(['title' => 'Spec', 'file_path' => 'crm/documents/spec.pdf', 'file_name' => 'spec.pdf']);
    $signed = $document->temporaryDownloadUrl();

    $this->app['auth']->logout();

    $this->get($signed)->assertRedirect('/crm/login');
});

it('records how a document was verified', function (): void {
    $user = actAs($this, ['commercial']);
    $document = Document::query()->create(['title' => 'Certificate', 'file_path' => 'crm/documents/c.pdf', 'file_name' => 'c.pdf']);

    Livewire::test(ManageDocuments::class)
        ->callAction(TestAction::make('verify')->table($document), data: ['verification_method' => 'Checked on the public register'])
        ->assertHasNoActionErrors();

    $document->refresh();

    expect($document->verified)->toBeTrue()
        ->and($document->verified_by)->toBe($user->id)
        ->and($document->verification_method)->toBe('Checked on the public register');
});
