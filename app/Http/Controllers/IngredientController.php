<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Content\IngredientCategories;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use LiteCrm\LiteCrm;
use LiteCrm\Models\CustomField;
use LiteCrm\Models\Document;
use LiteCrm\Models\Lookup;
use LiteCrm\Models\Organisation;
use LiteCrm\Models\Product;

/**
 * Ingredient directory, category pages and the product template (v3 §7.4).
 * Products come from CRM › Products and appear only when "publish on website" is ticked.
 */
class IngredientController extends Controller
{
    /** Directory filters: the product custom fields added by the feed-additives preset. */
    public const FILTERS = ['species' => 'Species', 'physical_form' => 'Form', 'function' => 'Function'];

    public function index(Request $request): View
    {
        $categories = $this->categories();
        $products = $this->publishedProducts();
        $selected = array_filter([
            'category' => $request->string('category')->toString(),
            ...collect(self::FILTERS)->keys()->mapWithKeys(fn (string $key): array => [$key => $request->string($key)->toString()])->all(),
        ]);

        $filtered = $products->filter(function (Product $product) use ($selected): bool {
            foreach ($selected as $key => $value) {
                $matches = $key === 'category'
                    ? IngredientCategories::slug((string) $product->category?->key) === $value
                    : in_array($value, (array) $product->getCustomValue($key), true);

                if (! $matches) {
                    return false;
                }
            }

            return true;
        });

        return view('pages.ingredients.index', [
            'categories' => $categories,
            'products' => $filtered->values(),
            'hasProducts' => $products->isNotEmpty(),
            'filters' => $this->filterOptions(),
            'selected' => $selected,
        ]);
    }

    public function category(string $category): View
    {
        $lookup = $this->categories()->firstWhere('slug', $category);
        abort_if($lookup === null, 404);

        return view('pages.ingredients.category', [
            'category' => $lookup,
            'products' => $this->publishedProducts()->filter(fn (Product $product): bool => $product->category_id === $lookup['id'])->values(),
        ]);
    }

    public function product(string $category, string $product): View
    {
        $lookup = $this->categories()->firstWhere('slug', $category);
        abort_if($lookup === null, 404);

        /** @var Product|null $record */
        $record = LiteCrm::model(Product::class)::query()
            ->where('slug', $product)
            ->where('publish_on_website', true)
            ->where('category_id', $lookup['id'])
            ->with(['category', 'suppliers'])
            ->first();
        abort_if($record === null, 404);

        return view('pages.ingredients.product', [
            'category' => $lookup,
            'product' => $record,
            'manufacturers' => $record->suppliers
                ->filter(fn (Organisation $organisation): bool => $organisation->permission_to_name_publicly && $organisation->permission_granted_on !== null)
                ->values(),
            'certificates' => $this->certificates($record),
        ]);
    }

    /**
     * Active product categories that have website content, in CRM order.
     *
     * @return Collection<int, array{id: int, key: string, slug: string, title: string, icon: string, summary: string, text: string, examples: list<string>}>
     */
    protected function categories(): Collection
    {
        $content = IngredientCategories::all();

        return LiteCrm::model(Lookup::class)::query()
            ->where('type', 'product_category')
            ->where('is_active', true)
            ->orderBy('sort')
            ->get()
            ->filter(fn (Lookup $lookup): bool => isset($content[$lookup->key]))
            ->map(fn (Lookup $lookup): array => [
                'id' => (int) $lookup->getKey(),
                'key' => $lookup->key,
                'slug' => IngredientCategories::slug($lookup->key),
                ...$content[$lookup->key],
            ])
            ->values();
    }

    /**
     * @return Collection<int, Product>
     */
    protected function publishedProducts(): Collection
    {
        return LiteCrm::model(Product::class)::query()
            ->where('publish_on_website', true)
            ->with('category')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return array<string, array{label: string, options: array<string, string>}>
     */
    protected function filterOptions(): array
    {
        $options = [];

        foreach (self::FILTERS as $key => $label) {
            $field = LiteCrm::model(CustomField::class)::query()
                ->where('entity', 'product')
                ->where('key', $key)
                ->where('is_active', true)
                ->first();

            if ($field !== null) {
                $options[$key] = [
                    'label' => $label,
                    'options' => collect($field->options ?? [])
                        ->mapWithKeys(fn (array $option): array => [$option['value'] => $option['label']])
                        ->all(),
                ];
            }
        }

        return $options;
    }

    /**
     * Certificates shown publicly: verified, not confidential, not expired (v3 §7.4 item 9).
     *
     * @return Collection<int, Document>
     */
    protected function certificates(Product $product): Collection
    {
        return $product->documents()
            ->where('verified', true)
            ->where('confidential', false)
            ->whereNotNull('certificate_number')
            ->where(fn ($query) => $query->whereNull('expires_on')->orWhereDate('expires_on', '>=', now()->toDateString()))
            ->orderBy('title')
            ->get();
    }
}
