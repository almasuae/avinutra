<?php

declare(strict_types=1);

use App\Settings\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('starts with the values from v5 §A5', function (): void {
    $settings = app(SiteSettings::class);

    expect($settings->brand)->toBe('AviNutra')
        ->and($settings->tagline)->toBe('Where Feed Science Meets Reliable Supply.')
        ->and($settings->emails['noreply'])->toBe('noreply@avinutra.com')
        ->and($settings->sg_incorporated)->toBeFalse()
        ->and($settings->pk_partner_role)->toBe('importer of record and local sales partner');
});

it('leaves unknown facts empty rather than inventing them', function (): void {
    $settings = app(SiteSettings::class);

    expect($settings->whatsapp_sales)->toBeNull()
        ->and($settings->whatsapp_nutrition)->toBeNull()
        ->and($settings->legal_name)->toBeNull()
        ->and($settings->uen)->toBeNull()
        ->and($settings->registered_office)->toBeNull()
        ->and($settings->pk_partner_name)->toBeNull()
        ->and($settings->pk_partner_city)->toBeNull();
});
