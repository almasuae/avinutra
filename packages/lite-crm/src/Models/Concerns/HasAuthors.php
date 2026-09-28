<?php

declare(strict_types=1);

namespace LiteCrm\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use LiteCrm\Contracts\CrmUser;
use LiteCrm\LiteCrm;

/**
 * owner_id, created_by and updated_by, filled from the signed-in user.
 * The owner defaults to the creator and can be reassigned.
 */
trait HasAuthors
{
    public static function bootHasAuthors(): void
    {
        static::creating(function (Model $model): void {
            $userId = Auth::id();

            $model->setAttribute('created_by', $model->getAttribute('created_by') ?? $userId);
            $model->setAttribute('owner_id', $model->getAttribute('owner_id') ?? $userId);
        });

        static::saving(function (Model $model): void {
            if (Auth::id() !== null) {
                $model->setAttribute('updated_by', Auth::id());
            }
        });
    }

    /**
     * @return BelongsTo<Model&CrmUser, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(LiteCrm::userModel(), 'owner_id');
    }

    /**
     * @return BelongsTo<Model&CrmUser, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(LiteCrm::userModel(), 'created_by');
    }

    /**
     * @return BelongsTo<Model&CrmUser, $this>
     */
    public function editor(): BelongsTo
    {
        return $this->belongsTo(LiteCrm::userModel(), 'updated_by');
    }
}
