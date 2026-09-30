<?php

declare(strict_types=1);

use App\Support\HeroStyle;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

/*
 * Header / hero separation: one setting (site.hero_style) picks the look of the home
 * hero and the inner-page hero band; the current look stays until the owner chooses.
 */

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(DatabaseSeeder::class));

it('keeps the current look by default', function (): void {
    expect(config('site.hero_style'))->toBe('current')
        ->and(HeroStyle::current())->toBe('current');

    $this->get('/')->assertSee('<html lang="en-GB" data-hero="current">', false);
});

it('applies the chosen style to the home hero, the inner hero band and the header', function (string $style): void {
    config(['site.hero_style' => $style]);

    foreach (['/', '/nutrition-services', '/about'] as $page) {
        $html = (string) $this->get($page)->assertOk()->getContent();

        expect($html)->toContain('data-hero="'.$style.'"')
            ->and(substr_count($html, 'class="site-hero'))->toBe(1)
            ->and($html)->toContain('class="site-header ');
    }
})->with(array_keys(HeroStyle::OPTIONS));

it('falls back to the current look for an unknown or empty setting', function (?string $value): void {
    config(['site.hero_style' => $value]);

    expect(HeroStyle::current())->toBe('current');
})->with(['E', '', null]);

it('previews a style with ?hero= on a local installation only', function (): void {
    $this->get('/?hero=C')->assertSee('data-hero="current"', false);

    app()->detectEnvironment(fn (): string => 'local');
    $this->get('/?hero=C')->assertSee('data-hero="C"', false);
    $this->get('/?hero=nonsense')->assertSee('data-hero="current"', false);
});

it('defines every style in the stylesheet', function (): void {
    $css = (string) file_get_contents(resource_path('css/app.css'));

    foreach (['A', 'B', 'C', 'D'] as $style) {
        expect($css)->toContain("[data-hero='{$style}']");
    }

    expect($css)->toContain('.site-hero {')
        ->and($css)->toContain('.site-header[data-scrolled]')
        ->and($css)->toContain('--color-hero-tint: #f2f7ef;');
});
