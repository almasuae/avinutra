<?php

declare(strict_types=1);

namespace LiteCrm\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use LiteCrm\Contracts\CrmUser;
use LiteCrm\Enums\PriceBasis;
use LiteCrm\Enums\PriceConfidence;
use LiteCrm\Enums\PriceSourceType;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Concerns\HasAuthors;
use LiteCrm\Models\Concerns\HasVisibility;
use LiteCrm\Models\Concerns\LogsCrmActivity;

/**
 * One observed price (price log): internal market intelligence. Never shown
 * publicly as an individual entry.
 *
 * @property int $id
 * @property Carbon $observed_on
 * @property int|null $product_id
 * @property int|null $source_organisation_id
 * @property PriceSourceType $source_type
 * @property PriceBasis $basis
 * @property string|null $location
 * @property string $price
 * @property string $currency
 * @property string $unit
 * @property Carbon|null $valid_until
 * @property string|null $reference
 * @property PriceConfidence $confidence
 * @property string|null $notes
 * @property int|null $owner_id
 * @property-read Product|null $product
 */
class PriceEntry extends Model
{
    use HasAuthors;
    use HasVisibility;
    use LogsCrmActivity;
    use SoftDeletes;

    protected $fillable = [
        'observed_on', 'product_id', 'source_organisation_id', 'source_type', 'basis', 'location', 'price', 'currency',
        'unit', 'valid_until', 'reference', 'confidence', 'notes', 'owner_id',
    ];

    protected $attributes = [
        'confidence' => 'reported',
    ];

    public function getTable(): string
    {
        return LiteCrm::table('price_entries');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'observed_on' => 'date',
            'valid_until' => 'date',
            'price' => 'decimal:4',
            'source_type' => PriceSourceType::class,
            'basis' => PriceBasis::class,
            'confidence' => PriceConfidence::class,
        ];
    }

    public static function crmModule(): string
    {
        return 'price_log';
    }

    protected static function restrictToUser(Builder $query, Model&CrmUser $user): void
    {
        $query->where('owner_id', $user->getKey());
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(LiteCrm::model(Product::class), 'product_id');
    }

    /**
     * @return BelongsTo<Organisation, $this>
     */
    public function sourceOrganisation(): BelongsTo
    {
        return $this->belongsTo(LiteCrm::model(Organisation::class), 'source_organisation_id');
    }
}
