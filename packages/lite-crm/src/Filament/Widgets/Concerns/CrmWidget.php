<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Widgets\Concerns;

use Filament\Facades\Filament;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use LiteCrm\Filament\Pages\CrmDashboard;
use LiteCrm\LiteCrm;
use LiteCrm\Support\Permissions;
use LiteCrm\Support\Visibility;

/**
 * Shared behaviour of the dashboard widgets: module/permission checks, the
 * page filters (user, territory, date range; Managers and Admins only) and
 * visibility-scoped queries.
 */
trait CrmWidget
{
    use InteractsWithPageFilters;

    /**
     * The module the widget reports on; the widget is hidden when it is off.
     */
    abstract protected static function module(): string;

    public static function canView(): bool
    {
        return LiteCrm::isModuleEnabled(static::module())
            && Permissions::allows(Filament::auth()->user(), static::module().'.view');
    }

    protected function user(): ?Model
    {
        $user = Filament::auth()->user();

        return $user instanceof Model ? $user : null;
    }

    /**
     * A page filter, when the user may filter.
     */
    protected function filter(string $name): mixed
    {
        if (! CrmDashboard::canFilter()) {
            return null;
        }

        $value = $this->pageFilters[$name] ?? null;

        return filled($value) ? $value : null;
    }

    /**
     * The chosen date range, or the given default.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function period(Carbon $defaultFrom, ?Carbon $defaultUntil = null): array
    {
        $from = $this->filter('from');
        $until = $this->filter('until');

        return [
            $from !== null ? Date::parse((string) $from)->startOfDay() : $defaultFrom,
            $until !== null ? Date::parse((string) $until)->endOfDay() : ($defaultUntil ?? Date::now()),
        ];
    }

    /**
     * A visibility-scoped query for a package model, with the user filter
     * applied to the given column.
     *
     * @template TModel of Model
     *
     * @param  class-string<TModel>  $model
     * @return Builder<TModel>
     */
    protected function scoped(string $model, ?string $ownerColumn = 'owner_id'): Builder
    {
        $query = Visibility::apply(LiteCrm::model($model)::query(), $this->user());

        if ($ownerColumn !== null && ($owner = $this->filter('owner_id')) !== null) {
            $query->where($ownerColumn, $owner);
        }

        return $query;
    }
}
