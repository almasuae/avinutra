<?php

declare(strict_types=1);

use App\Settings\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

it('builds every logo variant from the master PNG at the sizes in config/brand.php', function (): void {
    foreach (['logo-full', 'logo-compact', 'logo-mark'] as $variant) {
        $size = config("brand.variants.{$variant}");

        foreach (['png', 'webp'] as $format) {
            expect(public_path("brand/{$variant}.{$format}"))->toBeFile()
                ->and(public_path("brand/{$variant}@2x.{$format}"))->toBeFile();
        }

        [$width, $height] = getimagesize(public_path("brand/{$variant}@2x.png")) ?: [0, 0];
        expect([$width, $height])->toBe([$size['width'] * 2, $size['height'] * 2]);
    }

    foreach (['favicon-16.png', 'favicon-32.png', 'favicon.ico', 'apple-touch-icon.png', 'icon-192.png', 'icon-512.png', 'logo-email.png', 'og-image.png', 'logo-compact-boxed@2x.png'] as $file) {
        expect(public_path("brand/{$file}"))->toBeFile();
    }

    expect(getimagesize(public_path('brand/og-image.png'))[0] ?? null)->toBe(1200)
        ->and(getimagesize(public_path('brand/apple-touch-icon.png'))[0] ?? null)->toBe(180)
        ->and(file_get_contents(public_path('favicon.ico')))->toBe(file_get_contents(public_path('brand/favicon.ico')));
});

it('keeps only the PNG logo master (the SVG wrapper was removed)', function (): void {
    expect(base_path('docs/design/logo-source.png'))->toBeFile()
        ->and(base_path('docs/design/logo-source.svg'))->not->toBeFile();
});

it('shows the compact logo at 2x with explicit width and height in the header', function (): void {
    $size = config('brand.variants.logo-compact');

    $this->get('/')
        ->assertOk()
        ->assertSee('brand/logo-compact@2x.png', false)
        ->assertSee('brand/logo-compact@2x.webp', false)
        ->assertSee('width="'.$size['width'].'"', false)
        ->assertSee('height="'.$size['height'].'"', false)
        ->assertSee('brand/apple-touch-icon.png', false)
        ->assertSee('site.webmanifest', false);
});

it('never links to pages that do not exist yet', function (): void {
    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->not->toContain('href="#"')
        ->and($html)->not->toContain('Get in Touch');
});

it('links navigation items once their pages exist', function (): void {
    Route::get('/about', fn () => 'about')->name('about');
    Route::get('/contact', fn () => 'contact')->name('contact');
    Route::getRoutes()->refreshNameLookups();

    $this->get('/')
        ->assertOk()
        ->assertSee('href="'.url('/about').'"', false)
        ->assertSee('Get in Touch')
        ->assertDontSee('Nutrition Services');
});

it('shows the status statement and the site e-mails in the footer', function (): void {
    $this->get('/')
        ->assertOk()
        ->assertSee(app(SiteSettings::class)->statusStatement())
        ->assertSee('info@avinutra.com')
        ->assertDontSee('Pte. Ltd.');
});

it('words the company status from the site settings', function (): void {
    $site = app(SiteSettings::class);

    expect($site->statusStatement())->toBe('Our international trading company is being established in Singapore, with commercial activity initially focused on Pakistan.');

    $site->pk_partner_name = 'Partner Traders';
    $site->pk_partner_city = 'Lahore';
    expect($site->statusStatement())->toContain('conducted through our partner, Partner Traders, Lahore.');

    $site->sg_incorporated = true;
    $site->legal_name = 'Example Holdings';
    expect($site->statusStatement())->toBe('Example Holdings is incorporated in Singapore. In Pakistan, products are imported and supplied through Partner Traders.');

    $site->uen = '202600001A';
    expect($site->statusStatement())->toStartWith('Example Holdings (UEN 202600001A) is incorporated in Singapore.');
});

it('registers the design review page only in local development', function (): void {
    expect(Route::has('dev.design'))->toBeFalse();
    $this->get('/dev/design')->assertNotFound();
});

it('brands the CRM with the green primary colour, compact logo and mark favicon', function (): void {
    $this->get('/crm/login')
        ->assertOk()
        ->assertSee('brand/logo-compact@2x.png', false)
        ->assertSee('brand/favicon-32.png', false);
});
