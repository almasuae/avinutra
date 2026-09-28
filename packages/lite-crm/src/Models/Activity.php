<?php

declare(strict_types=1);

namespace LiteCrm\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use LiteCrm\Contracts\CrmUser;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Concerns\HasAuthors;
use LiteCrm\Models\Concerns\HasVisibility;
use LiteCrm\Models\Concerns\LogsCrmActivity;
use LiteCrm\Support\Visibility;

/**
 * An interaction (call, meeting, e-mail ...) logged against a record. Not to be
 * confused with the audit log.
 *
 * @property int $id
 * @property string|null $subject_type
 * @property int|null $subject_id
 * @property int|null $type_id
 * @property Carbon $occurred_at
 * @property int|null $duration_minutes
 * @property string $summary
 * @property string|null $outcome
 * @property string|null $next_step
 * @property int|null $owner_id
 * @property-read Model|null $subject
 */
class Activity extends Model
{
    use HasAuthors;
    use HasVisibility;
    use LogsCrmActivity;
    use SoftDeletes;

    protected $fillable = [
        'subject_type', 'subject_id', 'type_id', 'occurred_at', 'duration_minutes', 'summary', 'outcome', 'next_step', 'owner_id',
    ];

    public function getTable(): string
    {
        return LiteCrm::table('activities');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'duration_minutes' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // Logging an activity on an enquiry counts as its first response.
        static::created(function (Activity $activity): void {
            $subject = $activity->subject;

            if ($subject instanceof Enquiry) {
                $subject->markResponded();
            }
        });
    }

    public static function crmModule(): string
    {
        return 'activities';
    }

    /**
     * Own activities, those the user took part in, and those on records the user may see.
     */
    protected static function restrictToUser(Builder $query, Model&CrmUser $user): void
    {
        $query->where('owner_id', $user->getKey())
            ->orWhereHas('participantUsers', fn (Builder $users) => $users->whereKey($user->getKey()))
            ->orWhereHasMorph('subject', LiteCrm::recordModels(), fn (Builder $subjects) => Visibility::apply($subjects, $user));
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<Lookup, $this>
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(LiteCrm::model(Lookup::class), 'type_id');
    }

    /**
     * @return MorphToMany<Model&CrmUser, $this>
     */
    public function participantUsers(): MorphToMany
    {
        return $this->morphedByMany(LiteCrm::userModel(), 'participant', LiteCrm::table('activity_participants'), 'activity_id', 'participant_id');
    }

    /**
     * @return MorphToMany<Contact, $this>
     */
    public function participantContacts(): MorphToMany
    {
        return $this->morphedByMany(LiteCrm::model(Contact::class), 'participant', LiteCrm::table('activity_participants'), 'activity_id', 'participant_id');
    }
}
