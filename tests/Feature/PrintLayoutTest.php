<?php

declare(strict_types=1);

use App\Models\Article;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

/*
 * Print layout: the screen header and footer are hidden when printing (app.css); a
 * print-only header shows the compact logo, the site address and the print date, and
 * the tools and articles end with the Technical Notice in one sentence.
 */

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(DatabaseSeeder::class));

it('adds a print-only header with the logo, the site address and the date', function (): void {
    config(['app.url' => 'https://www.avinutra.com']);
    $html = (string) $this->get('/tools/landed-cost')->assertOk()->getContent();

    expect($html)->toMatch('~<div class="hidden[^"]*print:flex[^"]*" data-print-header>\s*<picture>.*logo-compact~s')
        ->and($html)->toContain('avinutra.com · Printed <span data-print-date>'.now()->format('j F Y').'</span>')
        ->and($html)->not->toContain('www.avinutra.com · Printed');
});

it('ends the tools and articles with the Technical Notice when printed, and no other page', function (): void {
    $article = Article::query()->published()->firstOrFail();

    foreach (['/tools/landed-cost', '/tools/methionine-value', route('knowledge.show', $article->slug)] as $page) {
        expect((string) $this->get($page)->getContent())->toMatch('~class="hidden[^"]*print:block[^"]*" data-print-notice>Technical Notice: ~');
    }

    foreach (['/', '/contact', '/legal/privacy'] as $page) {
        expect((string) $this->get($page)->getContent())->not->toContain('data-print-notice');
    }
});

it('keeps the screen header and footer out of print', function (): void {
    $css = (string) file_get_contents(resource_path('css/app.css'));

    expect($css)->toMatch('~@media print\s*\{\s*\[data-site-header\],\s*footer,~');
});
