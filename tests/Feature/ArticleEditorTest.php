<?php

declare(strict_types=1);

use App\Enums\ArticleStatus;
use App\Filament\Resources\Articles\Pages\CreateArticle;
use App\Filament\Resources\Articles\Pages\EditArticle;
use App\Models\Article;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Filament\Facades\Filament;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Textarea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LiteCrm\Filament\FormLayout;
use Livewire\Livewire;

/*
 * CRM form layout for long text: articles are written on full pages, with the body
 * editor at least 60% of the screen high (min. 500 px). The body of an existing
 * article must load into the editor (it once rendered empty inside a slide-over).
 */

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(DatabaseSeeder::class);
    Filament::setCurrentPanel(Filament::getPanel('crm'));

    $user = User::factory()->create();
    $user->crmProfile()->create(['time_zone' => 'UTC']);
    $user->syncRoles(['admin']);
    $user->saveAppAuthenticationSecret('JBSWY3DPEHPK3PXP');
    $this->actingAs($user->refresh());
});

it('opens articles as full pages, not in a modal or slide-over', function (): void {
    $article = Article::query()->published()->firstOrFail();

    $this->get('/crm/articles')->assertOk()->assertSee('/crm/articles/create', false);
    $this->get('/crm/articles/create')->assertOk()->assertSee('fi-fo-markdown-editor', false);
    $this->get("/crm/articles/{$article->getKey()}/edit")->assertOk()->assertSee('fi-fo-markdown-editor', false);
});

it('loads the existing body of a published article into the editor', function (): void {
    $article = Article::query()->published()->firstOrFail();
    $firstLine = trim(strtok((string) $article->body, "\n"));

    expect($firstLine)->not->toBe('');

    Livewire::test(EditArticle::class, ['record' => $article->getRouteKey()])
        ->assertFormSet(['body' => $article->body, 'title' => $article->title])
        ->assertFormFieldExists('body', fn (MarkdownEditor $editor): bool => $editor->getMinHeight() === FormLayout::TALL_EDITOR_MIN_HEIGHT);

    // The page itself carries the body to the editor (Livewire state), outside any modal.
    $html = (string) $this->get("/crm/articles/{$article->getKey()}/edit")->getContent();
    $state = str_replace(['\\n', '\\/'], ["\n", '/'], html_entity_decode($html, ENT_QUOTES));

    expect($state)->toContain(mb_substr($firstLine, 0, 60))
        ->and($html)->not->toMatch('~fi-modal[^>]*>(?:(?!</form>).)*fi-fo-markdown-editor~s');
});

it('lays out the article form: text two thirds, details one third, tall text fields', function (): void {
    Livewire::test(CreateArticle::class)
        ->assertFormFieldExists('summary', fn (Textarea $field): bool => $field->getRows() >= 4 && $field->shouldAutosize())
        ->assertFormFieldExists('outline', fn (Textarea $field): bool => $field->getRows() >= 8 && $field->shouldAutosize())
        ->assertFormFieldExists('body', fn (MarkdownEditor $field): bool => $field->getColumnSpan('default') === 'full');
});

it('saves sources with an internal note that the public page never shows', function (): void {
    $article = Article::query()->published()->firstOrFail();

    Livewire::test(EditArticle::class, ['record' => $article->getRouteKey()])
        ->fillForm(['sources' => [['title' => 'A long source title that stays readable', 'url' => 'https://example.org/a', 'date' => '2024', 'note' => 'Internal: title shortened.']]])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($article->refresh()->sources[0]['note'])->toBe('Internal: title shortened.');
    $this->get(route('knowledge.show', $article->slug))->assertOk()
        ->assertSee('A long source title that stays readable')
        ->assertDontSee('Internal: title shortened.');
    expect($article->status)->toBe(ArticleStatus::Published);
});

it('follows the CRM form layout rules on every host screen', function (): void {
    expect(FormLayout::violations(app_path('Filament/Resources')))->toBe([]);
});

it('moves the "title shortened" note of an already seeded source into its internal note', function (): void {
    $old = 'EFSA FEEDAP Panel (2018). Safety and efficacy of hydroxy analogue of methionine and its calcium salt for all animal species (title shortened; product name omitted). EFSA Journal 16(3):5198';
    $article = Article::query()->published()->firstOrFail();
    $article->update(['sources' => [['title' => $old, 'url' => 'https://doi.org/10.2903/j.efsa.2018.5198', 'date' => '2018'], ['title' => 'Other source']]]);

    (require database_path('migrations/2026_09_30_000002_shorten_seeded_source_title.php'))->up();

    $sources = $article->refresh()->sources;

    expect($sources[0]['title'])->toContain('calcium salt … for all animal species.')
        ->and($sources[0]['title'])->not->toContain('title shortened')
        ->and($sources[0]['note'])->toContain('Title shortened')
        ->and($sources[1])->toBe(['title' => 'Other source']);

    $this->get(route('knowledge.show', $article->slug))->assertOk()
        ->assertDontSee('title shortened')
        ->assertDontSee('product name omitted');
});
