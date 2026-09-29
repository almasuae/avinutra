<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use LiteCrm\Enquiries\ApiToken;
use LiteCrm\Enquiries\EnquiryConverter;
use LiteCrm\Enums\EnquiryStatus;
use LiteCrm\Filament\Pages\CrmSettingsPage;
use LiteCrm\Filament\Resources\Enquiries\Pages\ListEnquiries;
use LiteCrm\Filament\Resources\Enquiries\Pages\ViewEnquiry;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Contact;
use LiteCrm\Models\Enquiry;
use LiteCrm\Models\Lookup;
use LiteCrm\Models\Organisation;
use LiteCrm\Models\Task;
use LiteCrm\Notifications\EnquiryAssignedNotification;
use LiteCrm\Tests\Fixtures\User;
use LiteCrm\Tests\TestCase;
use Livewire\Livewire;

uses(TestCase::class);

beforeEach(function (): void {
    $this->seedCrm();
    Notification::fake();
    Filament::setCurrentPanel(Filament::getPanel('crm'));
});

function signIn(TestCase $test, array $roles, array $profile = []): User
{
    $user = $test->crmUser($roles, $profile);
    $test->withMfa($user);
    $test->actingAs($user->refresh());

    return $user;
}

function incoming(array $data = []): Enquiry
{
    return LiteCrm::captureEnquiry($data + [
        'name' => 'Sara Khan',
        'company' => 'Example Trading',
        'email' => 'sara@example.com',
        'city' => 'Lahore',
        'country' => 'Pakistan',
        'message' => 'Please send a quotation.',
    ], 'general');
}

it('shows new enquiries in the inbox and spam in its own tab', function (): void {
    signIn($this, ['commercial']);
    $new = incoming();
    $spam = incoming(['name' => 'Bot']);
    $spam->forceFill(['status' => EnquiryStatus::Spam])->save();

    Livewire::test(ListEnquiries::class)
        ->assertCanSeeTableRecords([$new])
        ->assertCanNotSeeTableRecords([$spam])
        ->set('activeTab', 'spam')
        ->assertCanSeeTableRecords([$spam])
        ->assertCanNotSeeTableRecords([$new]);
});

it('shows partners only the enquiries assigned to them', function (): void {
    // Website enquiries have no owner; the partner is assigned one of them.
    $theirs = incoming();
    $other = incoming(['name' => 'Someone else']);
    $partner = signIn($this, ['partner']);
    $theirs->update(['assignee_id' => $partner->id]);

    Livewire::test(ListEnquiries::class)
        ->set('activeTab', 'all')
        ->assertCanSeeTableRecords([$theirs])
        ->assertCanNotSeeTableRecords([$other]);
});

it('assigns an enquiry and e-mails the assignee', function (): void {
    signIn($this, ['manager']);
    $colleague = $this->crmUser(['commercial']);
    $enquiry = incoming();

    Livewire::test(ViewEnquiry::class, ['record' => $enquiry->getRouteKey()])
        ->callAction('assign', data: ['assignee_id' => $colleague->id])
        ->assertHasNoActionErrors();

    expect($enquiry->refresh()->status)->toBe(EnquiryStatus::Assigned)
        ->and($enquiry->assignee_id)->toBe($colleague->id)
        ->and($enquiry->first_response_at)->toBeNull();

    Notification::assertSentTo($colleague, EnquiryAssignedNotification::class);
});

it('e-mails the assignee when a just-captured enquiry is assigned in code', function (): void {
    $colleague = $this->crmUser(['commercial']);
    $enquiry = incoming();

    $enquiry->update(['assignee_id' => $colleague->id]);

    Notification::assertSentTo($colleague, EnquiryAssignedNotification::class);
});

it('records the first response when work starts or an activity is logged', function (): void {
    signIn($this, ['commercial']);
    $started = incoming();
    $logged = incoming(['name' => 'Other']);

    Livewire::test(ViewEnquiry::class, ['record' => $started->getRouteKey()])->callAction('start');
    $logged->activities()->create(['occurred_at' => now(), 'summary' => 'Replied by e-mail']);

    expect($started->refresh()->status)->toBe(EnquiryStatus::InProgress)
        ->and($started->first_response_at)->not->toBeNull()
        ->and($logged->refresh()->first_response_at)->not->toBeNull();
});

it('moves an enquiry to spam and back without deleting it', function (): void {
    signIn($this, ['commercial']);
    $enquiry = incoming();

    Livewire::test(ViewEnquiry::class, ['record' => $enquiry->getRouteKey()])->callAction('markSpam');
    expect($enquiry->refresh()->status)->toBe(EnquiryStatus::Spam);

    Livewire::test(ViewEnquiry::class, ['record' => $enquiry->getRouteKey()])->callAction('notSpam');
    expect($enquiry->refresh()->status)->toBe(EnquiryStatus::New)
        ->and(Enquiry::query()->count())->toBe(1);
});

it('converts an enquiry into a new organisation, contact, activity and task', function (): void {
    $user = signIn($this, ['commercial']);
    $enquiry = incoming();

    Livewire::test(ViewEnquiry::class, ['record' => $enquiry->getRouteKey()])
        ->mountAction('convert')
        ->assertActionDataSet([
            'organisation_action' => EnquiryConverter::NEW,
            'contact_action' => EnquiryConverter::NEW,
            'organisation.name' => 'Example Trading',
            'contact.first_name' => 'Sara',
            'contact.last_name' => 'Khan',
        ])
        ->setActionData(['task_title' => 'Prepare quotation'])
        ->callMountedAction()
        ->assertHasNoActionErrors();

    $enquiry->refresh();
    $organisation = Organisation::query()->sole();
    $contact = Contact::query()->sole();

    expect($enquiry->status)->toBe(EnquiryStatus::Converted)
        ->and($enquiry->organisation_id)->toBe($organisation->id)
        ->and($enquiry->contact_id)->toBe($contact->id)
        ->and($enquiry->converted_by)->toBe($user->id)
        ->and($organisation->city)->toBe('Lahore')
        ->and($contact->organisation_id)->toBe($organisation->id)
        ->and($contact->email)->toBe('sara@example.com')
        ->and($contact->activities()->count())->toBe(1)
        ->and(Task::query()->sole()->title)->toBe('Prepare quotation');
});

it('offers existing matches first and links to them without creating anything', function (): void {
    signIn($this, ['commercial']);
    $organisation = Organisation::query()->create(['name' => 'Example Trading', 'city' => 'Lahore']);
    $contact = $organisation->contacts()->create(['last_name' => 'Khan', 'email' => 'SARA@example.com']);
    $enquiry = incoming();

    Livewire::test(ViewEnquiry::class, ['record' => $enquiry->getRouteKey()])
        ->mountAction('convert')
        ->assertActionDataSet([
            'organisation_action' => EnquiryConverter::EXISTING,
            'organisation_id' => $organisation->id,
            'contact_action' => EnquiryConverter::EXISTING,
            'contact_id' => $contact->id,
        ])
        ->callMountedAction()
        ->assertHasNoActionErrors();

    expect(Organisation::query()->count())->toBe(1)
        ->and(Contact::query()->count())->toBe(1)
        ->and($enquiry->refresh()->organisation_id)->toBe($organisation->id)
        ->and($enquiry->contact_id)->toBe($contact->id);
});

it('refuses to create a duplicate organisation or contact from the form', function (): void {
    signIn($this, ['commercial']);
    Organisation::query()->create(['name' => '  example trading ', 'city' => 'LAHORE']);
    Contact::query()->create(['last_name' => 'Khan', 'email' => 'sara@example.com']);
    $enquiry = incoming();

    Livewire::test(ViewEnquiry::class, ['record' => $enquiry->getRouteKey()])
        ->callAction('convert', data: [
            'organisation_action' => EnquiryConverter::NEW,
            'organisation' => ['name' => 'Example Trading', 'city' => 'Lahore'],
            'contact_action' => EnquiryConverter::NEW,
            'contact' => ['last_name' => 'Khan', 'email' => 'sara@example.com'],
        ])
        ->assertHasActionErrors(['organisation.name', 'contact.email']);

    expect(Organisation::query()->count())->toBe(1)
        ->and(Contact::query()->count())->toBe(1)
        ->and($enquiry->refresh()->status)->toBe(EnquiryStatus::New);
});

it('treats the same name in another city as a different organisation', function (): void {
    signIn($this, ['commercial']);
    Organisation::query()->create(['name' => 'Example Trading', 'city' => 'Karachi']);
    $enquiry = incoming();

    Livewire::test(ViewEnquiry::class, ['record' => $enquiry->getRouteKey()])
        ->callAction('convert', data: [
            'organisation_action' => EnquiryConverter::NEW,
            'organisation' => ['name' => 'Example Trading', 'city' => 'Lahore'],
            'contact_action' => EnquiryConverter::NONE,
        ])
        ->assertHasNoActionErrors();

    expect(Organisation::query()->pluck('city')->sort()->values()->all())->toBe(['Karachi', 'Lahore']);
});

it('refuses duplicates even when the existing record is hidden from the user', function (): void {
    $partner = $this->crmUser(['partner']);
    $this->actingAs($this->crmUser(['commercial']));
    Organisation::query()->create(['name' => 'Example Trading', 'city' => 'Lahore']);
    $enquiry = incoming();
    $enquiry->update(['assignee_id' => $partner->id]);

    expect(fn () => app(EnquiryConverter::class)->convert($enquiry, [
        'organisation_action' => EnquiryConverter::NEW,
        'organisation' => ['name' => 'Example Trading', 'city' => 'Lahore'],
    ], $partner))->toThrow(ValidationException::class);

    expect(Organisation::query()->count())->toBe(1)
        ->and($enquiry->refresh()->status)->not->toBe(EnquiryStatus::Converted);
});

it('never converts the same enquiry twice', function (): void {
    $user = $this->crmUser(['commercial']);
    $this->actingAs($user);
    $enquiry = incoming();
    $converter = app(EnquiryConverter::class);

    $converter->convert($enquiry, ['organisation_action' => EnquiryConverter::NEW, 'organisation' => ['name' => 'Example Trading', 'city' => 'Lahore']], $user);

    expect(fn () => $converter->convert($enquiry->refresh(), ['organisation_action' => EnquiryConverter::NEW, 'organisation' => ['name' => 'Other', 'city' => 'Lahore']], $user))
        ->toThrow(ValidationException::class);

    expect(Organisation::query()->count())->toBe(1);
});

it('matches names and e-mails case- and space-insensitively', function (): void {
    $converter = app(EnquiryConverter::class);
    Organisation::query()->create(['name' => 'Example Trading', 'city' => null]);
    Contact::query()->create(['last_name' => 'Khan', 'email' => 'Sara@Example.com']);

    expect($converter->duplicateOrganisation(' example TRADING', ''))->not->toBeNull()
        ->and($converter->duplicateOrganisation('Example Trading', 'Lahore'))->toBeNull()
        ->and($converter->duplicateContact('sara@example.COM '))->not->toBeNull()
        ->and(EnquiryConverter::splitName('Sara  Ali Khan'))->toBe(['Sara Ali', 'Khan'])
        ->and(EnquiryConverter::splitName('Khan'))->toBe([null, 'Khan']);
});

it('lets admins generate and revoke the API token from CRM settings', function (): void {
    signIn($this, ['admin']);

    Livewire::test(CrmSettingsPage::class)->callAction('rotateApiToken');
    expect(app(ApiToken::class)->exists())->toBeTrue();

    Livewire::test(CrmSettingsPage::class)->callAction('revokeApiToken');
    expect(app(ApiToken::class)->exists())->toBeFalse();
});

it('lets each enquiry type have its own mailbox', function (): void {
    $type = Lookup::query()->where('type', 'enquiry_type')->where('key', 'quotation')->firstOrFail();
    $type->update(['meta' => ['mailbox' => 'sales@example.com']]);

    expect($type->refresh()->meta)->toBe(['mailbox' => 'sales@example.com']);
});
