<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Content\IngredientCategories;
use App\Content\Services;
use App\Models\Article;
use App\Models\GlossaryTerm;
use App\Models\TeamProfile;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Lookup;
use LiteCrm\Models\Product;

/**
 * robots.txt and sitemap.xml (v3 §5, §9). The CRM is disallowed and never listed.
 */
class SeoController extends Controller
{
    /** Public pages that are always listed (route names). */
    public const STATIC_ROUTES = [
        'home', 'about', 'about.company', 'about.editorial-policy',
        'services', 'services.feed-mills', 'services.request-sourcing',
        'ingredients', 'ingredients.methionine',
        'quality', 'suppliers', 'suppliers.how-we-work', 'suppliers.apply',
        'tools', 'tools.methionine-value', 'tools.landed-cost',
        'knowledge', 'contact', 'ask-a-nutritionist',
        'legal.privacy', 'legal.terms', 'legal.cookies', 'legal.technical-disclaimer',
    ];

    public function robots(): Response
    {
        $crm = '/'.trim((string) config('lite-crm.path', 'crm'), '/');
        $api = '/'.explode('/', trim((string) config('lite-crm.enquiry_api.path', 'crm-api/enquiries'), '/'))[0];

        $lines = [
            'User-agent: *',
            "Disallow: {$crm}",
            "Disallow: {$api}",
            'Disallow: /livewire',
            '',
            'Sitemap: '.route('sitemap'),
        ];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function sitemap(): Response
    {
        $urls = [];
        $add = function (string $name, array $parameters = [], ?string $lastModified = null) use (&$urls): void {
            if (Route::has($name)) {
                $urls[] = ['loc' => route($name, $parameters), 'lastmod' => $lastModified];
            }
        };

        foreach (self::STATIC_ROUTES as $name) {
            $add($name);
        }

        foreach (Services::slugs() as $slug) {
            $add('services.show', ['service' => $slug]);
        }

        $categories = LiteCrm::model(Lookup::class)::query()->where('type', 'product_category')->where('is_active', true)->get()
            ->filter(fn (Lookup $lookup): bool => isset(IngredientCategories::all()[$lookup->key]));
        foreach ($categories as $category) {
            $add('ingredients.category', ['category' => IngredientCategories::slug($category->key)]);
        }

        $products = LiteCrm::model(Product::class)::query()->where('publish_on_website', true)->with('category')->get();
        foreach ($products as $product) {
            if ($product->category !== null && isset(IngredientCategories::all()[$product->category->key])) {
                $add('ingredients.product', ['category' => IngredientCategories::slug($product->category->key), 'product' => $product->slug], $product->updated_at?->toAtomString());
            }
        }

        foreach (Article::query()->published()->get() as $article) {
            $add('knowledge.show', ['slug' => $article->slug], ($article->updated_at ?? $article->published_at)?->toAtomString());
        }

        if (GlossaryTerm::query()->published()->exists()) {
            $add('knowledge.glossary');
        }

        if (TeamProfile::query()->public()->exists()) {
            $add('about.team');
        }

        return response()->view('seo.sitemap', ['urls' => $urls], 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
