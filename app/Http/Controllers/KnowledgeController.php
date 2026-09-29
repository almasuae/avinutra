<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ArticleCategory;
use App\Models\Article;
use App\Models\GlossaryTerm;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Knowledge Centre (v3 §7.9): published articles only, and the glossary.
 */
class KnowledgeController extends Controller
{
    public function index(Request $request): View
    {
        $category = ArticleCategory::tryFrom($request->string('category')->toString());

        $articles = Article::query()->published()
            ->when($category, fn ($query) => $query->where('category', $category?->value))
            ->with(['author', 'reviewer'])
            ->latest('published_at')
            ->get();

        return view('pages.knowledge.index', [
            'articles' => $articles,
            'category' => $category,
            'categories' => collect(ArticleCategory::cases())
                ->filter(fn (ArticleCategory $case): bool => Article::query()->published()->where('category', $case->value)->exists())
                ->values(),
            'termCount' => GlossaryTerm::query()->published()->count(),
        ]);
    }

    public function show(string $slug): View
    {
        $article = Article::query()->published()->where('slug', $slug)->with(['author', 'reviewer'])->first();
        abort_if($article === null, 404);

        return view('pages.knowledge.show', ['article' => $article]);
    }

    public function glossary(): View
    {
        $terms = GlossaryTerm::query()->published()->orderBy('term')->get();
        abort_if($terms->isEmpty(), 404);

        return view('pages.knowledge.glossary', [
            'groups' => $terms->groupBy(fn (GlossaryTerm $term): string => mb_strtoupper(mb_substr($term->term, 0, 1))),
        ]);
    }
}
