<?php

declare(strict_types=1);

namespace LiteCrm\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use LiteCrm\Contracts\CrmUser;
use LiteCrm\Enums\Incoterm;
use LiteCrm\Enums\QuotationStatus;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Concerns\HasAuthors;
use LiteCrm\Models\Concerns\HasRelatedRecords;
use LiteCrm\Models\Concerns\HasVisibility;
use LiteCrm\Models\Concerns\LogsCrmActivity;
use LiteCrm\Support\Sequence;
use LiteCrm\Support\Visibility;

/**
 * A quotation record (PDF output comes later). The number is assigned
 * automatically ("{prefix}-{year}-{0001}") and never reused; the contracting
 * entity defaults from the host's resolver (LiteCrm::resolveContractingEntityUsing).
 *
 * @property int $id
 * @property string $number
 * @property int|null $organisation_id
 * @property int|null $contact_id
 * @property int|null $product_id
 * @property int|null $opportunity_id
 * @property string|null $quantity
 * @property string|null $unit
 * @property string|null $price
 * @property string|null $currency
 * @property Incoterm|null $incoterm
 * @property string|null $port
 * @property string|null $payment_terms
 * @property Carbon|null $valid_until
 * @property QuotationStatus $status
 * @property string|null $contracting_entity
 * @property string|null $notes
 * @property int|null $owner_id
 * @property-read Organisation|null $organisation
 */
class Quotation extends Model
{
    use HasAuthors;
    use HasRelatedRecords;
    use HasVisibility;
    use LogsCrmActivity {
        HasRelatedRecords::activities insteadof LogsCrmActivity;
    }
    use SoftDeletes;

    protected $fillable = [
        'organisation_id', 'contact_id', 'product_id', 'opportunity_id', 'quantity', 'unit', 'price', 'currency',
        'incoterm', 'port', 'payment_terms', 'valid_until', 'status', 'contracting_entity', 'notes', 'owner_id',
    ];

    protected $attributes = [
        'status' => 'draft',
    ];

    public function getTable(): string
    {
        return LiteCrm::table('quotations');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'price' => 'decimal:4',
            'incoterm' => Incoterm::class,
            'valid_until' => 'date',
            'status' => QuotationStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Quotation $quotation): void {
            $quotation->number = static::nextNumber();

            if (blank($quotation->contracting_entity)) {
                $quotation->contracting_entity = LiteCrm::contractingEntity();
            }
        });
    }

    public static function nextNumber(): string
    {
        $year = (int) Date::now()->format('Y');
        $number = Sequence::next('quotation', $year);

        return strtr((string) config('lite-crm.quotations.number_format', '{prefix}-{year}-{number}'), [
            '{prefix}' => (string) config('lite-crm.quotations.number_prefix', 'Q'),
            '{year}' => (string) $year,
            '{number}' => str_pad((string) $number, (int) config('lite-crm.quotations.number_padding', 4), '0', STR_PAD_LEFT),
        ]);
    }

    public static function crmModule(): string
    {
        return 'quotations';
    }

    protected static function restrictToUser(Builder $query, Model&CrmUser $user): void
    {
        $query->where('owner_id', $user->getKey())
            ->orWhereHas('organisation', fn (Builder $organisations) => Visibility::apply($organisations, $user));
    }

    public function isExpired(): bool
    {
        return $this->valid_until !== null && $this->valid_until->isPast() && ! $this->valid_until->isToday();
    }

    public function total(): ?float
    {
        return $this->quantity === null || $this->price === null ? null : round((float) $this->quantity * (float) $this->price, 2);
    }

    /**
     * @return BelongsTo<Organisation, $this>
     */
    public function organisation(): BelongsTo
    {
        return $this->belongsTo(LiteCrm::model(Organisation::class), 'organisation_id');
    }

    /**
     * @return BelongsTo<Contact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(LiteCrm::model(Contact::class), 'contact_id');
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(LiteCrm::model(Product::class), 'product_id');
    }

    /**
     * @return BelongsTo<Opportunity, $this>
     */
    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(LiteCrm::model(Opportunity::class), 'opportunity_id');
    }
}
