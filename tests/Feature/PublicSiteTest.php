<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

// The header and footer read the site settings (company status, e-mails).
uses(RefreshDatabase::class);

it('serves the home page', function (): void {
    $this->get('/')
        ->assertOk()
        ->assertSee('Poultry Nutrition Expertise. Global Feed Ingredient Supply.')
        ->assertHeaderMissing('X-Robots-Tag');
});

it('allows search engines on the public site but not the CRM', function (): void {
    $robots = (string) file_get_contents(public_path('robots.txt'));

    expect($robots)
        ->toContain('User-agent: *')
        ->toContain('Disallow: /crm')
        ->not->toMatch('/^Disallow:\s*\/\s*$/m');
});

it('never shows the suffix Pte. Ltd. before incorporation', function (): void {
    $this->get('/')->assertDontSee('Pte. Ltd.');
});
