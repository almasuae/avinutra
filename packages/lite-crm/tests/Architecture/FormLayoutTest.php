<?php

declare(strict_types=1);

use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Textarea;
use LiteCrm\Filament\FormLayout;
use LiteCrm\Tests\TestCase;

/*
 * CRM form layout (CLAUDE.md): screens with long text open as full pages or in a
 * 7xl modal, never a half-width slide-over; textareas start at 4 rows and grow;
 * editors are at least 400 px high and full width.
 */

uses(TestCase::class);

it('opens every long-text form of the package full-width, with tall text fields', function (): void {
    expect(FormLayout::violations(dirname(__DIR__, 2).'/src/Filament/Resources'))->toBe([]);
});

it('gives textareas 4 rows that grow, and editors 400 px, by default', function (): void {
    $textarea = Textarea::make('notes');
    $editor = MarkdownEditor::make('body');

    expect($textarea->getRows())->toBe(FormLayout::MIN_TEXTAREA_ROWS)
        ->and($textarea->shouldAutosize())->toBeTrue()
        ->and($editor->getMinHeight())->toBe(FormLayout::EDITOR_MIN_HEIGHT)
        ->and($editor->getColumnSpan('default'))->toBe('full')
        ->and(FormLayout::modalWidth()->value)->toBe('7xl')
        ->and((string) FormLayout::styles())->toContain('position: sticky');
});
