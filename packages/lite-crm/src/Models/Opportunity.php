<?php

declare(strict_types=1);

namespace LiteCrm\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Illuminate\Validation\ValidationException;
use LiteCrm\Contracts\CrmUser;
use LiteCrm\Events\OpportunityLost;
use LiteCrm\Events\OpportunityStageChanged;
use LiteCrm\Events\OpportunityWon;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Concerns\HasAuthors;
use LiteCrm\Models\Concerns\HasCustomFields;
use LiteCrm\Models\Concerns\HasRelatedRecords;
use LiteCrm\Models\Concerns\HasTags;
use LiteCrm\Models\Concerns\HasVisibility;
use LiteCrm\Models\Concerns\LogsCrmActivity;
use LiteCrm\Support\Visibility;

/**
 * A potential deal moving through a pipeline's stages.
 *
 * Changing the stage sets the probability to the stage's default (unless it is
 * changed in the same save), stamps closed_at on won/lost stages, logs a
 * "stage change" activity and fires OpportunityStageChanged, and then
 * OpportunityWon or OpportunityLost where it applies.
 *
 * @property int $id
 * @property string $name
 * @property int $pipeline_id
 * @property int|null $stage_id
 * @property int|null $organisation_id
 * @property int|null $contact_id
 * @property int|null $product_id
 * @property string|null $volume
 * @property string|null $unit
 * @property string|null $value
 * @property string|null $currency
 * @property int $probability
 * @property Carbon|null $expected_close_date
 * @property string|null $next_step
 * @property Carbon|null $next_step_date
 * @property int|null $lost_reason_id
 * @property Carbon|null $closed_at
 * @property int $sort
 * @property string|null $notes
 * @property array<string, mixed>|null $custom
 * @property int|null $enquiry_id
 * @property int|null $owner_id
 * @property-read Pipeline|null $pipeline
 * @property-read PipelineStage $stage
 * @property-read Organisation|null $organisation
 */
class Opportunity extends Model
{
    use HasAuthors;
    use HasCustomFields;
    use HasRelatedRecords;
    use HasTags;
    use HasVisibility {
        scopeVisibleTo as protected scopeVisibleToRecords;
    }
    use LogsCrmActivity {
        HasRelatedRecords::activities insteadof LogsCrmActivity;
    }
    use SoftDeletes;

    protected string $customFieldEntity = 'opportunity';

    protected $fillable = [
        'name', 'pipeline_id', 'stage_id', 'organisation_id', 'contact_id', 'product_id', 'volume', 'unit', 'value',
        'currency', 'probability', 'expected_close_date', 'next_step', 'next_step_date', 'lost_reason_id', 'sort',
        'notes', 'custom', 'enquiry_id', 'owner_id',
    ];

    public function getTable(): string
    {
        return LiteCrm::table('opportunities');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'volume' => 'decimal:3',
            'value' => 'decimal:2',
            'probability' => 'integer',
            'expected_close_date' => 'date',
            'next_step_date' => 'date',
            'closed_at' => 'datetime',
            'sort' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Opportunity $opportunity): void {
            /** @var class-string<PipelineStage> $stageModel */
            $stageModel = LiteCrm::model(PipelineStage::class);

            if ($opportunity->stage_id === null && filled($opportunity->pipeline_id)) {
                $opportunity->stage_id = (int) $stageModel::query()->where('pipeline_id', $opportunity->pipeline_id)->orderBy('sort')->value('id');
            }

            /** @var PipelineStage|null $stage */
            $stage = $stageModel::query()->find($opportunity->stage_id);

            if ($stage === null || $stage->pipeline_id !== (int) $opportunity->pipeline_id) {
                throw ValidationException::withMessages(['stage_id' => __('lite-crm::opportunities.errors.stage_not_in_pipeline')]);
            }

            if ($opportunity->isDirty('stage_id')) {
                if (! $opportunity->isDirty('probability')) {
                    $opportunity->probability = $stage->probability;
                }

                $opportunity->closed_at = ($stage->is_won || $stage->is_lost) ? Date::now() : null;

                if (! $stage->is_lost) {
                    $opportunity->lost_reason_id = null;
                }
            }
        });

        // "created" and "updated" rather than "saved": wasRecentlyCreated stays true for the
        // lifetime of an instance, so it cannot tell an insert from a later update.
        static::created(fn (Opportunity $opportunity) => $opportunity->stageReached(null));

        static::updated(function (Opportunity $opportunity): void {
            if ($opportunity->wasChanged('stage_id')) {
                $opportunity->stageReached($opportunity->getOriginal('stage_id'));
            }
        });
    }

    /**
     * Logs the move (not for a new opportunity) and fires the stage events.
     */
    protected function stageReached(mixed $fromStageId): void
    {
        /** @var class-string<PipelineStage> $stageModel */
        $stageModel = LiteCrm::model(PipelineStage::class);
        /** @var PipelineStage|null $from */
        $from = $fromStageId !== null ? $stageModel::query()->find($fromStageId) : null;
        /** @var PipelineStage $to */
        $to = $stageModel::query()->findOrFail($this->stage_id);

        if ($from !== null) {
            $this->logStageChange($from, $to);
        }

        OpportunityStageChanged::dispatch($this, $from, $to);

        if ($to->is_won) {
            OpportunityWon::dispatch($this);
        } elseif ($to->is_lost) {
            OpportunityLost::dispatch($this);
        }
    }

    protected function logStageChange(PipelineStage $from, PipelineStage $to): void
    {
        if (! LiteCrm::isModuleEnabled('activities')) {
            return;
        }

        $this->activities()->create([
            'type_id' => LiteCrm::model(Lookup::class)::query()->where('type', 'activity_type')->where('key', 'stage_change')->value('id'),
            'occurred_at' => Date::now(),
            'summary' => __('lite-crm::opportunities.stage_changed', ['from' => $from->name, 'to' => $to->name]),
        ]);
    }

    public static function crmModule(): string
    {
        return 'opportunities';
    }

    /**
     * Own opportunities, and those of organisations the user may see.
     */
    protected static function restrictToUser(Builder $query, Model&CrmUser $user): void
    {
        $query->where('owner_id', $user->getKey())
            ->orWhereHas('organisation', fn (Builder $organisations) => Visibility::apply($organisations, $user));
    }

    /**
     * Record visibility, plus pipelines restricted to some roles.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeVisibleTo(Builder $query, mixed $user): Builder
    {
        $query = $this->scopeVisibleToRecords($query, $user);

        if ($user instanceof CrmUser && ! LiteCrm::isSuperAdmin($user)) {
            $query->whereHas('pipeline', fn (Builder $pipelines) => Pipeline::restrictToRoles($pipelines, $user, 'visible_to_roles'));
        }

        return $query;
    }

    /**
     * Value × probability, in the opportunity's own currency.
     */
    public function weightedValue(): ?float
    {
        return $this->value === null ? null : round((float) $this->value * $this->probability / 100, 2);
    }

    public function isOpen(): bool
    {
        return $this->closed_at === null;
    }

    /**
     * @return BelongsTo<Pipeline, $this>
     */
    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(LiteCrm::model(Pipeline::class), 'pipeline_id');
    }

    /**
     * @return BelongsTo<PipelineStage, $this>
     */
    public function stage(): BelongsTo
    {
        return $this->belongsTo(LiteCrm::model(PipelineStage::class), 'stage_id');
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
     * @return BelongsTo<Lookup, $this>
     */
    public function lostReason(): BelongsTo
    {
        return $this->belongsTo(LiteCrm::model(Lookup::class), 'lost_reason_id');
    }

    /**
     * @return BelongsTo<Enquiry, $this>
     */
    public function enquiry(): BelongsTo
    {
        return $this->belongsTo(LiteCrm::model(Enquiry::class), 'enquiry_id');
    }

    /**
     * @return HasMany<Quotation, $this>
     */
    public function quotations(): HasMany
    {
        return $this->hasMany(LiteCrm::model(Quotation::class), 'opportunity_id');
    }

    public function customFieldTypeKey(): ?string
    {
        return $this->pipeline?->key;
    }
}
