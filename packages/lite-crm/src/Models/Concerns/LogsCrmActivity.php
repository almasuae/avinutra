<?php

declare(strict_types=1);

namespace LiteCrm\Models\Concerns;

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Audit-logs every change to a model: who changed which attributes, from what to what.
 */
trait LogsCrmActivity
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('crm')
            ->logAll()
            // A model may list extra attributes to keep out of the log (e.g. secrets).
            ->logExcept(array_merge(['created_at', 'updated_at'], property_exists($this, 'activityLogExcept') ? $this->activityLogExcept : []))
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
