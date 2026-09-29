<?php

declare(strict_types=1);

namespace LiteCrm\Filament;

use Filament\Actions\Action;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Support\Enums\Width;
use FilesystemIterator;
use Illuminate\Support\HtmlString;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Layout rules for forms with long text (CLAUDE.md, "CRM form layout"):
 *  - screens with long text open as full pages, or at least in a 7xl modal (never a
 *    half-width slide-over);
 *  - textareas start at 4 rows and grow with their content;
 *  - Markdown and rich-text editors are full width in their column and at least 400 px
 *    high; they grow with the content and keep their toolbar visible while scrolling.
 *
 * The defaults are applied in LiteCrmServiceProvider (config lite-crm.forms.defaults);
 * the styles are added to the panel by LiteCrmPlugin.
 */
final class FormLayout
{
    public const MIN_TEXTAREA_ROWS = 4;

    public const EDITOR_MIN_HEIGHT = '400px';

    /** For the main text of a full-page editor (e.g. an article body). */
    public const TALL_EDITOR_MIN_HEIGHT = 'max(500px, 60vh)';

    public static function modalWidth(): Width
    {
        return Width::SevenExtraLarge;
    }

    /**
     * A create or edit action for a form with long text: a 7xl modal.
     *
     * @template T of Action
     *
     * @param  T  $action
     * @return T
     */
    public static function wide(Action $action): Action
    {
        $action->slideOver(false)->modalWidth(self::modalWidth());

        return $action;
    }

    public static function configureDefaults(): void
    {
        Textarea::configureUsing(function (Textarea $textarea): void {
            $textarea->rows(self::MIN_TEXTAREA_ROWS)->autosize();
        });

        MarkdownEditor::configureUsing(function (MarkdownEditor $editor): void {
            $editor->minHeight(self::EDITOR_MIN_HEIGHT)->columnSpanFull();
        });

        RichEditor::configureUsing(function (RichEditor $editor): void {
            $editor->columnSpanFull();
        });
    }

    /**
     * Styles Filament's compiled CSS does not provide: editor heights and a toolbar
     * that stays visible (below the panel's top bar) while a long text is scrolled.
     */
    public static function styles(): HtmlString
    {
        return new HtmlString(<<<'HTML'
            <style>
                .fi-fo-markdown-editor, .fi-fo-markdown-editor .fi-input-wrp-content-ctn, .fi-fo-rich-editor { overflow: visible; }
                .fi-fo-markdown-editor .EasyMDEContainer .editor-toolbar,
                .fi-fo-rich-editor .fi-fo-rich-editor-toolbar {
                    position: sticky; top: 4rem; z-index: 20;
                    background: var(--color-white, #fff); border-top-left-radius: .5rem; border-top-right-radius: .5rem;
                }
                .dark .fi-fo-markdown-editor .EasyMDEContainer .editor-toolbar,
                .dark .fi-fo-rich-editor .fi-fo-rich-editor-toolbar { background: var(--gray-900, #18181b); }
                .fi-fo-rich-editor .fi-fo-rich-editor-content { min-height: 400px; }
                .fi-modal .fi-fo-markdown-editor .EasyMDEContainer .editor-toolbar,
                .fi-modal .fi-fo-rich-editor .fi-fo-rich-editor-toolbar { top: 0; }
            </style>
            HTML);
    }

    /**
     * Checks the resources under $resources against these rules (used by the tests of
     * the package and of the host): a resource whose form has long text and
     * no create/edit page must open its create and edit actions with FormLayout::wide().
     *
     * @return list<string> the violations
     */
    public static function violations(string $resources): array
    {
        $violations = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($resources, FilesystemIterator::SKIP_DOTS));

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $path = $file->getPathname();
            $code = (string) file_get_contents($path);
            $name = str_replace('\\', '/', substr($path, strlen($resources) + 1));

            if (preg_match('/->rows\(\s*[0-3]\s*\)/', $code)) {
                $violations[] = "{$name}: a textarea with fewer than 4 rows";
            }

            if (preg_match('/->slideOver\(\s*\)/', $code)) {
                $violations[] = "{$name}: a half-width slide-over (use a full page or FormLayout::wide())";
            }

            $longText = preg_match('/(Textarea|MarkdownEditor|RichEditor)::make\(/', $code) === 1;

            // Resources with long text and only a "Manage" page open their forms in modals.
            if ($longText && str_ends_with($name, 'Resource.php') && ! str_contains($code, "'create' =>")
                && preg_match('/EditAction::make\(\)/', $code) && ! str_contains($code, 'FormLayout::wide(EditAction::make())')) {
                $violations[] = "{$name}: the edit modal of a long-text form is not FormLayout::wide()";
            }

            if ($longText && str_contains($name, 'RelationManager') && preg_match('/(?<!wide\()(Create|Edit)Action::make\(\)/', $code)) {
                $violations[] = "{$name}: a relation-manager action on a long-text form is not FormLayout::wide()";
            }
        }

        // Manage pages of long-text resources.
        foreach (glob($resources.'/*/Pages/Manage*.php') ?: [] as $page) {
            $resourceFile = (glob(dirname($page, 2).'/*Resource.php') ?: [])[0] ?? null;
            $resource = $resourceFile === null ? '' : (string) file_get_contents($resourceFile);

            if (preg_match('/(Textarea|MarkdownEditor|RichEditor)::make\(/', $resource) !== 1) {
                continue;
            }

            if (preg_match('/(?<!wide\()CreateAction::make\(\)/', (string) file_get_contents($page))) {
                $violations[] = str_replace('\\', '/', substr($page, strlen($resources) + 1)).': the create modal of a long-text form is not FormLayout::wide()';
            }
        }

        return $violations;
    }
}
