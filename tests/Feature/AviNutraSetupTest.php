<?php

declare(strict_types=1);

use App\Models\User;
use App\Providers\AppServiceProvider;
use App\Settings\SiteSettings;
use Database\Seeders\AviNutraSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LiteCrm\LiteCrm;
use LiteCrm\Models\CustomField;
use LiteCrm\Models\Lookup;

uses(RefreshDatabase::class);

it('seeds the CRM with the feed-additives preset and enquiry mailboxes, but no users', function (): void {
    $this->seed(DatabaseSeeder::class);

    $mailbox = fn (string $type): ?string => Lookup::query()->where('type', 'enquiry_type')->where('key', $type)->sole()->meta['mailbox'] ?? null;

    expect(User::query()->count())->toBe(0)
        ->and(CustomField::query()->where('key', 'methionine_use_t_year')->exists())->toBeTrue()
        ->and(LiteCrm::quotationPrefix())->toBe('AVN-Q')
        ->and($mailbox('general'))->toBe('info@avinutra.com')
        ->and($mailbox('ask_nutritionist'))->toBe('nutrition@avinutra.com')
        ->and($mailbox('quotation'))->toBe('sales@avinutra.com')
        ->and($mailbox('supplier_application'))->toBe('partners@avinutra.com');
});

it('can run the AviNutra seeder again without duplicates', function (): void {
    $this->seed(DatabaseSeeder::class);
    $counts = [Lookup::query()->count(), CustomField::query()->count()];

    $this->seed(AviNutraSeeder::class);

    expect([Lookup::query()->count(), CustomField::query()->count()])->toBe($counts);
});

it('names the contracting entity on quotations from the site settings', function (): void {
    $site = app(SiteSettings::class);

    $site->sg_incorporated = false;
    $site->pk_partner_name = null;
    $site->save();
    expect(AppServiceProvider::contractingEntity())->toBeNull()
        ->and(LiteCrm::contractingEntity())->toBeNull();

    $site->pk_partner_name = 'Partner Traders';
    $site->pk_partner_city = 'Lahore';
    $site->save();
    expect(LiteCrm::contractingEntity())->toBe('Partner Traders, Lahore');

    $site->sg_incorporated = true;
    $site->legal_name = 'Example Holdings';
    $site->save();
    expect(LiteCrm::contractingEntity())->toBe('Example Holdings');
});

it('drains the queue from the scheduler, as the server has no Supervisor', function (): void {
    $commands = collect(app(Schedule::class)->events())->pluck('command')->implode("\n");

    expect($commands)->toContain('queue:work --stop-when-empty')
        ->toContain('lite-crm:send-digests');
});
