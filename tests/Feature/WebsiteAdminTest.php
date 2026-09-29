<?php

declare(strict_types=1);

use App\Filament\Pages\SiteSettingsPage;
use App\Filament\Resources\Articles\ArticleResource;
use App\Filament\Resources\CalculatorDefaults\CalculatorDefaultResource;
use App\Filament\Resources\Faqs\FaqResource;
use App\Filament\Resources\GlossaryTerms\GlossaryTermResource;
use App\Filament\Resources\PageSeo\PageSeoResource;
use App\Filament\Resources\TeamProfiles\TeamProfileResource;
use App\Models\User;
use App\Settings\SiteSettings;
use Database\Seeders\ContentGapTaskSeeder;
use Database\Seeders\DatabaseSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LiteCrm\Models\Task;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(DatabaseSeeder::class);
    Filament::setCurrentPanel(Filament::getPanel('crm'));
});

function websiteUser(string $role): User
{
    $user = User::factory()->create();
    $user->crmProfile()->create(['time_zone' => 'Asia/Karachi']);
    $user->syncRoles([$role]);
    $user->saveAppAuthenticationSecret('JBSWY3DPEHPK3PXP');

    return $user->refresh();
}

it('opens every Website screen for an admin', function (string $url): void {
    $this->actingAs(websiteUser('admin'))->get($url)->assertOk();
})->with([
    fn () => SiteSettingsPage::getUrl(),
    fn () => ArticleResource::getUrl(),
    fn () => TeamProfileResource::getUrl(),
    fn () => GlossaryTermResource::getUrl(),
    fn () => FaqResource::getUrl(),
    fn () => CalculatorDefaultResource::getUrl(),
    fn () => PageSeoResource::getUrl(),
]);

it('keeps the Website screens from users without website.manage', function (): void {
    $this->actingAs(websiteUser('commercial'));

    expect(SiteSettingsPage::canAccess())->toBeFalse()
        ->and(ArticleResource::canViewAny())->toBeFalse();
    $this->get(SiteSettingsPage::getUrl())->assertForbidden();
});

it('saves Site settings, including the enquiry response time', function (): void {
    $this->actingAs(websiteUser('admin'));

    Livewire::test(SiteSettingsPage::class)
        ->assertSet('data.enquiry_response_time', 'within one working day')
        ->set('data.enquiry_response_time', 'within two working days')
        ->set('data.whatsapp_sales', '+92 300 1234567')
        ->call('save')
        ->assertHasNoErrors();

    $site = app(SiteSettings::class)->refresh();
    expect($site->enquiry_response_time)->toBe('within two working days')
        ->and($site->whatsapp_sales)->toBe('+92 300 1234567');
});

it('requires a legal name before the company is marked incorporated', function (): void {
    $this->actingAs(websiteUser('admin'));

    Livewire::test(SiteSettingsPage::class)
        ->set('data.sg_incorporated', true)
        ->set('data.legal_name', '')
        ->call('save')
        ->assertHasErrors(['data.legal_name']);

    expect(app(SiteSettings::class)->refresh()->sg_incorporated)->toBeFalse();
});

it('seeds every open content gap as a task, once, and assigns it to the first admin', function (): void {
    $open = ContentGapTaskSeeder::openGaps(base_path('CONTENT-GAPS.md'));
    $tasks = fn () => Task::query()->where('title', 'like', ContentGapTaskSeeder::TITLE_PREFIX.'%');

    expect($open)->not->toBeEmpty()
        ->and($tasks()->count())->toBe(count($open))
        ->and($tasks()->whereNotNull('assignee_id')->count())->toBe(0);

    $admin = websiteUser('admin');
    $this->seed(ContentGapTaskSeeder::class);

    expect($tasks()->count())->toBe(count($open))
        ->and($tasks()->where('assignee_id', $admin->getKey())->count())->toBe(count($open));
});
