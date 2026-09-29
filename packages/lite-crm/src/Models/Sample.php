<?php

declare(strict_types=1);

namespace LiteCrm\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use LiteCrm\Contracts\CrmUser;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Concerns\HasAuthors;
use LiteCrm\Models\Concerns\HasCustomFields;
use LiteCrm\Models\Concerns\HasRelatedRecords;
use LiteCrm\Models\Concerns\HasVisibility;
use LiteCrm\Models\Concerns\LogsCrmActivity;
use LiteCrm\Support\Visibility;

/**
 * A sample sent to an organisation, and what they thought of it.
 *
 * @property int $id
 * @property int|null $organisation_id
 * @property int|null $contact_id
 * @property int|null $product_id
 * @property int|null $supplier_id
 * @property int|null $opportunity_id
 * @property string|null $lot_number
 * @property string|null $quantity
 * @property Carbon|null $sent_on
 * @property string|null $courier
 * @property string|null $tracking
 * @property Carbon|null $received_on
 * @property string|null $feedback
 * @property array<string, mixed>|null $custom
 * @property int|null $owner_id
 * @property-read Organisation|null $organisation
 * @property-read Product|null $product
 */
class Sample extends Model
{
    use HasAuthors;
    use HasCustomFields;
    use HasRelatedRecords;
    use HasVisibility;
    use LogsCrmActivity {
        HasRelatedRecords::activities insteadof LogsCrmActivity;
    }
    use SoftDeletes;

    public const PREPARING = 'preparing';

    public const SENT = 'sent';

    public const RECEIVED = 'received';

    public const FEEDBACK = 'feedback';

    protected string $customFieldEntity = 'sample';

    protected $fillable = [
        'organisation_id', 'contact_id', 'product_id', 'supplier_id', 'opportunity_id', 'lot_number', 'quantity',
        'sent_on', 'courier', 'tracking', 'received_on', 'feedback', 'custom', 'owner_id',
    ];

    public function getTable(): string
    {
        return LiteCrm::table('samples');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sent_on' => 'date',
            'received_on' => 'date',
        ];
    }

    public static function crmModule(): string
    {
        return 'samples';
    }

    protected static function restrictToUser(Builder $query, Model&CrmUser $user): void
    {
        $query->where('owner_id', $user->getKey())
            ->orWhereHas('organisation', fn (Builder $organisations) => Visibility::apply($organisations, $user));
    }

    /**
     * Where the sample is: preparing → sent → received → feedback.
     */
    public function stage(): string
    {
        return match (true) {
            filled($this->feedback) => self::FEEDBACK,
            $this->received_on !== null => self::RECEIVED,
            $this->sent_on !== null => self::SENT,
            default => self::PREPARING,
        };
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeInProgress(Builder $query): Builder
    {
        return $query->where(fn (Builder $query) => $query->whereNull('feedback')->orWhere('feedback', ''));
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
     * @return BelongsTo<Organisation, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(LiteCrm::model(Organisation::class), 'supplier_id');
    }

    /**
     * @return BelongsTo<Opportunity, $this>
     */
    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(LiteCrm::model(Opportunity::class), 'opportunity_id');
    }
}
