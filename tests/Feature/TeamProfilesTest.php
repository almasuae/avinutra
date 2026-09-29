<?php

declare(strict_types=1);

use App\Enums\ArticleStatus;
use App\Enums\TeamRole;
use App\Filament\Resources\Articles\Pages\ManageArticles;
use App\Filament\Resources\TeamProfiles\Pages\ManageTeamProfiles;
use App\Models\Article;
use App\Models\TeamProfile;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

/*
 * Team profiles (v3 §7.2): a profile is public only when published with written
 * consent on file, its date and the signed consent document (private disk). No
 * sample people are seeded; these tests create their own.
 */

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(DatabaseSeeder::class);
    Filament::setCurrentPanel(Filament::getPanel('crm'));
    Storage::fake('local');
});

function teamAdmin(): User
{
    $user = User::factory()->create();
    $user->crmProfile()->create(['time_zone' => 'UTC']);
    $user->syncRoles(['admin']);
    $user->saveAppAuthenticationSecret('JBSWY3DPEHPK3PXP');

    return $user->refresh();
}

function consentedProfile(string $name = 'Dr Example Adviser'): TeamProfile
{
    return TeamProfile::query()->create([
        'name' => $name, 'role_type' => TeamRole::Adviser, 'consent_on_file' => true,
        'consent_date' => now(), 'consent_document_path' => 'team-consents/'.str($name)->slug().'.pdf', 'is_published' => true,
    ]);
}

it('seeds no team profiles', function (): void {
    expect(TeamProfile::query()->withTrashed()->count())->toBe(0);
});

it('refuses to publish a profile without consent, its date and the signed document', function (array $consent): void {
    expect(fn () => TeamProfile::query()->create(['name' => 'Dr Example', 'role_type' => TeamRole::Adviser, 'is_published' => true, ...$consent]))
        ->toThrow(ValidationException::class, TeamProfile::CONSENT_REQUIRED);

    expect(TeamProfile::query()->count())->toBe(0);
})->with([
    'nothing' => [[]],
    'no document' => [['consent_on_file' => true, 'consent_date' => '2026-09-01']],
    'no date' => [['consent_on_file' => true, 'consent_document_path' => 'team-consents/a.pdf']],
    'box not ticked' => [['consent_date' => '2026-09-01', 'consent_document_path' => 'team-consents/a.pdf']],
]);

it('unpublishing is always possible, and removing the consent document is refused while published', function (): void {
    $profile = consentedProfile();

    expect(fn () => $profile->update(['consent_document_path' => null]))->toThrow(ValidationException::class);

    $profile->refresh()->update(['is_published' => false, 'consent_document_path' => null]);
    expect($profile->refresh()->is_published)->toBeFalse();
});

it('rejects publishing in the CRM form until the consent document is uploaded, and stores it privately', function (): void {
    $this->actingAs(teamAdmin());

    Livewire::test(ManageTeamProfiles::class)
        ->callAction('create', data: ['name' => 'Dr Example Adviser', 'role_type' => TeamRole::Adviser->value, 'consent_on_file' => true, 'consent_date' => '2026-09-01', 'is_published' => true])
        ->assertHasActionErrors(['consent_document_path' => 'required', 'is_published']);

    expect(TeamProfile::query()->count())->toBe(0);

    Livewire::test(ManageTeamProfiles::class)
        ->callAction('create', data: [
            'name' => 'Dr Example Adviser', 'role_type' => TeamRole::Adviser->value, 'consent_on_file' => true, 'consent_date' => '2026-09-01',
            'consent_document_path' => UploadedFile::fake()->create('consent.pdf', 50, 'application/pdf'), 'is_published' => true,
        ])
        ->assertHasNoActionErrors();

    $profile = TeamProfile::query()->sole();

    expect($profile->isPublic())->toBeTrue()
        ->and($profile->consent_document_path)->toStartWith('team-consents/');
    Storage::disk('local')->assertExists($profile->consent_document_path);
    expect(Storage::disk('public')->exists($profile->consent_document_path))->toBeFalse();
});

it('keeps the Team page, its links and its sitemap entry hidden without a public profile', function (): void {
    // Consent complete but not published; and published-looking data without the document.
    TeamProfile::query()->create(['name' => 'Dr Hidden Person', 'role_type' => TeamRole::Adviser, 'consent_on_file' => true, 'consent_date' => now(), 'consent_document_path' => 'team-consents/x.pdf']);
    TeamProfile::query()->create(['name' => 'Dr Unconsented', 'role_type' => TeamRole::Consultant]);

    $this->get('/about/team')->assertNotFound();
    $this->get('/about')->assertOk()->assertDontSee(route('about.team'), false);
    $this->get('/sitemap.xml')->assertOk()->assertDontSee(route('about.team'), false);

    consentedProfile();

    $this->get('/about/team')->assertOk()->assertSee('Dr Example Adviser')->assertDontSee('Dr Hidden Person')->assertDontSee('Dr Unconsented');
    $this->get('/sitemap.xml')->assertSee(route('about.team'), false);
});

it('offers only public profiles as author and reviewer in the Articles editor', function (): void {
    $public = consentedProfile();
    $private = TeamProfile::query()->create(['name' => 'Dr Private Person', 'role_type' => TeamRole::Adviser]);
    $article = Article::query()->published()->firstOrFail();
    $this->actingAs(teamAdmin());

    Livewire::test(ManageArticles::class)
        ->mountAction(TestAction::make('edit')->table($article))
        ->assertSchemaComponentExists('author_id', checkComponentUsing: fn ($field): bool => array_keys($field->getOptions()) === [$public->getKey()])
        ->assertSchemaComponentExists('reviewer_id', checkComponentUsing: fn ($field): bool => array_keys($field->getOptions()) === [$public->getKey()])
        ->setActionData(['author_id' => $private->getKey()])
        ->callMountedAction()
        ->assertHasActionErrors(['author_id']);

    expect($article->refresh()->author_id)->toBeNull();
});

it('links the byline to the profiles, and lists each person\'s articles on the Team page', function (): void {
    $author = consentedProfile('Dr Example Author');
    $reviewer = consentedProfile('Dr Example Reviewer');
    $article = Article::query()->published()->firstOrFail();
    $article->update(['author_id' => $author->getKey(), 'reviewer_id' => $reviewer->getKey(), 'last_reviewed_on' => '2026-09-15']);

    $this->get(route('knowledge.show', $article->slug))->assertOk()
        ->assertSee('href="'.route('about.team').'#dr-example-author"', false)
        ->assertSee('Reviewed by', false)
        ->assertSee('href="'.route('about.team').'#dr-example-reviewer"', false)
        ->assertSee('Last reviewed 15 September 2026');

    $html = (string) $this->get('/about/team')->assertOk()->getContent();

    expect($html)->toContain('id="dr-example-author"')
        ->and($html)->toContain('id="dr-example-reviewer"')
        ->and($html)->toMatch('~id="dr-example-author".*Articles written.*'.preg_quote(e($article->title), '~').'.*id="dr-example-reviewer"~s')
        ->and($html)->toMatch('~id="dr-example-reviewer".*Articles reviewed.*'.preg_quote(e($article->title), '~').'~s');

    // A draft is never listed.
    $article->update(['status' => ArticleStatus::Draft->value]);
    $this->get('/about/team')->assertDontSee(e($article->title), false);
});
