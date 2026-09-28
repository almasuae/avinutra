<?php

declare(strict_types=1);

namespace LiteCrm\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use LiteCrm\Contracts\CrmUser;
use LiteCrm\Support\Permissions;

/**
 * Who may see a record. Users holding "{module}.view_all" see everything;
 * everyone else (e.g. Partners) sees only records restricted by
 * restrictToUser(): their own, assigned, or in their territory.
 */
trait HasVisibility
{
    /**
     * The permission module, e.g. "organisations".
     */
    abstract public static function crmModule(): string;

    /**
     * Limit $query to records a user without "view_all" may see.
     *
     * @param  Builder<static>  $query
     */
    abstract protected static function restrictToUser(Builder $query, Model&CrmUser $user): void;

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeVisibleTo(Builder $query, mixed $user): Builder
    {
        if (! $user instanceof CrmUser || ! $user instanceof Model || ! Permissions::allows($user, static::crmModule().'.view')) {
            return $query->whereRaw('1 = 0');
        }

        if (Permissions::allows($user, static::crmModule().'.view_all')) {
            return $query;
        }

        return $query->where(fn (Builder $query) => static::restrictToUser($query, $user));
    }

    public function isVisibleTo(mixed $user): bool
    {
        return static::query()
            ->withoutGlobalScopes()
            ->whereKey($this->getKey())
            ->visibleTo($user)
            ->exists();
    }

    /**
     * The user's territory, used by territory-based restrictions.
     */
    protected static function territoryOf(Model&CrmUser $user): ?int
    {
        return $user->getCrmProfile()->territory_id;
    }
}
