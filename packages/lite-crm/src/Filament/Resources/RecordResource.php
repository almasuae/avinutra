<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Resources;

use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use LiteCrm\LiteCrm;
use LiteCrm\LiteCrmPlugin;
use LiteCrm\Support\Visibility;

/**
 * Base for resources over record models (organisations, contacts ...).
 *
 * Authorisation goes through the model policies (LiteCrm\Policies); lists only
 * ever contain records the signed-in user may see (HasVisibility::visibleTo).
 */
abstract class RecordResource extends Resource
{
    protected static bool $isScopedToTenant = false;

    public static function crmModule(): string
    {
        /** @var class-string<Model> $model */
        $model = static::getModel();

        return method_exists($model, 'crmModule') ? $model::crmModule() : '';
    }

    public static function canAccess(): bool
    {
        return LiteCrm::isModuleEnabled(static::crmModule()) && parent::canAccess();
    }

    public static function getNavigationGroup(): ?string
    {
        return LiteCrmPlugin::recordNavigationGroup();
    }

    /**
     * @return Builder<Model>
     */
    public static function getEloquentQuery(): Builder
    {
        /** @var Builder<Model> $query */
        $query = parent::getEloquentQuery();

        return Visibility::apply($query, Filament::auth()->user());
    }

    /**
     * Deleted records stay reachable (for Admins to restore) through the trashed filter.
     *
     * @return Builder<Model>
     */
    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
