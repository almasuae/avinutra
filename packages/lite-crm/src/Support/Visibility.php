<?php

declare(strict_types=1);

namespace LiteCrm\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Applies HasVisibility::scopeVisibleTo() to a query whose model is only known
 * at runtime (polymorphic relations, Filament callbacks).
 */
class Visibility
{
    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public static function apply(Builder $query, mixed $user): Builder
    {
        return $query->scopes(['visibleTo' => [$user]]);
    }
}
