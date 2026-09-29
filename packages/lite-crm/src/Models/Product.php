<?php

declare(strict_types=1);

namespace LiteCrm\Models;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use LiteCrm\Contracts\CrmUser;
use LiteCrm\Enums\ProductAvailability;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Concerns\HasAuthors;
use LiteCrm\Models\Concerns\HasCustomFields;
use LiteCrm\Models\Concerns\HasRelatedRecords;
use LiteCrm\Models\Concerns\HasTags;
use LiteCrm\Models\Concerns\HasVisibility;
use LiteCrm\Models\Concerns\LogsCrmActivity;
use LiteCrm\Support\Permissions;

/**
 * A product in the catalogue. "availability" and "publish_on_website" drive
 * what a host website may show; changing them needs extra permissions.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property int|null $category_id
 * @property string|null $description
 * @property list<array{parameter?: string, value?: string, unit?: string}>|null $specification
 * @property string|null $packaging
 * @property string|null $storage
 * @property string|null $shelf_life
 * @property ProductAvailability $availability
 * @property bool $publish_on_website
 * @property array<string, mixed>|null $custom
 * @property int|null $owner_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Lookup|null $category
 */
class Product extends Model
{
    use HasAuthors;
    use HasCustomFields;
    use HasRelatedRecords;
    use HasTags;
    use HasVisibility;
    use LogsCrmActivity {
        HasRelatedRecords::activities insteadof LogsCrmActivity;
    }
    use SoftDeletes;

    protected string $customFieldEntity = 'product';

    protected $fillable = [
        'name', 'slug', 'category_id', 'description', 'specification', 'packaging', 'storage', 'shelf_life',
        'availability', 'publish_on_website', 'custom', 'owner_id',
    ];

    protected $attributes = [
        'availability' => 'information',
        'publish_on_website' => false,
    ];

    public function getTable(): string
    {
        return LiteCrm::table('products');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'specification' => 'array',
            'availability' => ProductAvailability::class,
            'publish_on_website' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Product $product): void {
            if (blank($product->slug)) {
                $product->slug = static::uniqueSlug($product->name, $product->getKey());
            }

            $user = Auth::user();

            if ($user === null) {
                return; // Seeders, presets and console commands.
            }

            if ($product->isDirty('availability') && $product->availability === ProductAvailability::Available
                && ! Permissions::allows($user, 'products.mark_available')) {
                throw new AuthorizationException(__('lite-crm::products.errors.mark_available'));
            }

            if ($product->isDirty('publish_on_website') && ! Permissions::allows($user, 'website.manage')) {
                throw new AuthorizationException(__('lite-crm::products.errors.publish'));
            }
        });
    }

    public static function uniqueSlug(string $name, mixed $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'product';
        $slug = $base;
        $counter = 2;

        while (static::withTrashed()->where('slug', $slug)->when($ignoreId, fn (Builder $query) => $query->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }

    public static function crmModule(): string
    {
        return 'products';
    }

    protected static function restrictToUser(Builder $query, Model&CrmUser $user): void
    {
        $query->where('owner_id', $user->getKey());
    }

    public function customFieldTypeKey(): ?string
    {
        return $this->category?->key;
    }

    /**
     * @return BelongsTo<Lookup, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(LiteCrm::model(Lookup::class), 'category_id');
    }

    /**
     * Organisations that supply this product, with notes.
     *
     * @return BelongsToMany<Organisation, $this>
     */
    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(LiteCrm::model(Organisation::class), LiteCrm::table('product_suppliers'), 'product_id', 'organisation_id')
            ->withPivot('notes')
            ->withTimestamps();
    }
}
