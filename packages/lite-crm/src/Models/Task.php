<?php

declare(strict_types=1);

namespace LiteCrm\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use LiteCrm\Contracts\CrmUser;
use LiteCrm\Enums\TaskPriority;
use LiteCrm\Enums\TaskStatus;
use LiteCrm\Events\TaskAssigned;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Concerns\HasAuthors;
use LiteCrm\Models\Concerns\HasVisibility;
use LiteCrm\Models\Concerns\LogsCrmActivity;
use LiteCrm\Support\Visibility;

/**
 * @property int $id
 * @property string|null $taskable_type
 * @property int|null $taskable_id
 * @property string $title
 * @property string|null $description
 * @property int|null $assignee_id
 * @property Carbon|null $due_at
 * @property TaskPriority $priority
 * @property TaskStatus $status
 * @property Carbon|null $completed_at
 * @property int|null $owner_id
 * @property-read Model|null $taskable
 * @property-read (Model&CrmUser)|null $assignee
 */
class Task extends Model
{
    use HasAuthors;
    use HasVisibility;
    use LogsCrmActivity;
    use SoftDeletes;

    protected $fillable = [
        'taskable_type', 'taskable_id', 'title', 'description', 'assignee_id', 'due_at', 'priority', 'status', 'owner_id',
    ];

    protected $attributes = [
        'priority' => 'normal',
        'status' => 'open',
    ];

    public function getTable(): string
    {
        return LiteCrm::table('tasks');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'completed_at' => 'datetime',
            'priority' => TaskPriority::class,
            'status' => TaskStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Task $task): void {
            if ($task->isDirty('status')) {
                $task->completed_at = $task->status === TaskStatus::Done ? Date::now() : null;
            }
        });

        static::created(function (Task $task): void {
            if ($task->assignee_id !== null) {
                TaskAssigned::dispatch($task);
            }
        });

        static::updated(function (Task $task): void {
            if ($task->assignee_id !== null && $task->wasChanged('assignee_id')) {
                TaskAssigned::dispatch($task);
            }
        });
    }

    public static function crmModule(): string
    {
        return 'tasks';
    }

    /**
     * Tasks assigned to or owned by the user, and tasks on records the user may see.
     */
    protected static function restrictToUser(Builder $query, Model&CrmUser $user): void
    {
        $query->where('assignee_id', $user->getKey())
            ->orWhere('owner_id', $user->getKey())
            ->orWhereHasMorph('taskable', LiteCrm::recordModels(), fn (Builder $records) => Visibility::apply($records, $user));
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', [TaskStatus::Open->value, TaskStatus::InProgress->value]);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->open()->whereNotNull('due_at')->where('due_at', '<', Date::now());
    }

    public function isOverdue(): bool
    {
        return ! $this->status->isClosed() && $this->due_at !== null && $this->due_at->isPast();
    }

    public function markDone(): void
    {
        $this->status = TaskStatus::Done;
        $this->save();
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function taskable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<Model&CrmUser, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(LiteCrm::userModel(), 'assignee_id');
    }
}
