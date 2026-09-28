<?php

declare(strict_types=1);

namespace LiteCrm\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Activity;
use LiteCrm\Models\Document;
use LiteCrm\Models\Task;

/**
 * Activities, tasks and documents attached to a record.
 */
trait HasRelatedRecords
{
    /**
     * @return MorphMany<Activity, $this>
     */
    public function activities(): MorphMany
    {
        return $this->morphMany(LiteCrm::model(Activity::class), 'subject')->latest('occurred_at');
    }

    /**
     * @return MorphMany<Task, $this>
     */
    public function tasks(): MorphMany
    {
        return $this->morphMany(LiteCrm::model(Task::class), 'taskable');
    }

    /**
     * @return MorphMany<Document, $this>
     */
    public function documents(): MorphMany
    {
        return $this->morphMany(LiteCrm::model(Document::class), 'documentable');
    }
}
