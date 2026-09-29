<?php

declare(strict_types=1);

namespace LiteCrm\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use LiteCrm\Contracts\CrmUser;
use LiteCrm\Enums\TrialStatus;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Concerns\HasAuthors;
use LiteCrm\Models\Concerns\HasCustomFields;
use LiteCrm\Models\Concerns\HasRelatedRecords;
use LiteCrm\Models\Concerns\HasVisibility;
use LiteCrm\Models\Concerns\LogsCrmActivity;
use LiteCrm\Support\Visibility;

/**
 * A product trial at an organisation. Results may be published only when
 * consent_to_publish is recorded (with its date). KPIs are custom fields.
 *
 * @property int $id
 * @property string $name
 * @property int|null $organisation_id
 * @property int|null $product_id
 * @property int|null $opportunity_id
 * @property string|null $protocol
 * @property Carbon|null $start_date
 * @property Carbon|null $end_date
 * @property TrialStatus $status
 * @property string|null $result_summary
 * @property bool $consent_to_publish
 * @property Carbon|null $consent_date
 * @property array<string, mixed>|null $custom
 * @property int|null $owner_id
 * @property-read Organisation|null $organisation
 */
class Trial extends Model
{
    use HasAuthors;
    use HasCustomFields;
    use HasRelatedRecords;
    use HasVisibility;
    use LogsCrmActivity {
        HasRelatedRecords::activities insteadof LogsCrmActivity;
    }
    use SoftDeletes;

    protected string $customFieldEntity = 'trial';

    protected $fillable = [
        'name', 'organisation_id', 'product_id', 'opportunity_id', 'protocol', 'start_date', 'end_date', 'status',
        'result_summary', 'consent_to_publish', 'consent_date', 'custom', 'owner_id',
    ];

    protected $attributes = [
        'status' => 'planned',
        'consent_to_publish' => false,
    ];

    public function getTable(): string
    {
        return LiteCrm::table('trials');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'status' => TrialStatus::class,
            'consent_to_publish' => 'boolean',
            'consent_date' => 'date',
        ];
    }

    public static function crmModule(): string
    {
        return 'trials';
    }

    protected static function restrictToUser(Builder $query, Model&CrmUser $user): void
    {
        $query->where('owner_id', $user->getKey())
            ->orWhereHas('organisation', fn (Builder $organisations) => Visibility::apply($organisations, $user));
    }

    /**
     * Results may appear publicly only with the customer's recorded, dated consent.
     */
    public function mayBePublished(): bool
    {
        return $this->consent_to_publish && $this->consent_date !== null;
    }

    /**
     * @return BelongsTo<Organisation, $this>
     */
    public function organisation(): BelongsTo
    {
        return $this->belongsTo(LiteCrm::model(Organisation::class), 'organisation_id');
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
