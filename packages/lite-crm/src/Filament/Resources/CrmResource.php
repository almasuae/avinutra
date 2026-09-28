<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Resources;

use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;
use LiteCrm\LiteCrm;
use LiteCrm\LiteCrmPlugin;
use LiteCrm\Support\Permissions;
use UnitEnum;

/**
 * Base for package resources. Access is decided by CRM permissions rather than
 * model policies, so the package never registers policies for host models.
 *
 * - viewAny / view            → static::$viewPermission
 * - create / update / reorder → static::$managePermission
 * - delete / restore          → Admins only (deletion is soft and restorable)
 * - forceDelete               → never
 */
abstract class CrmResource extends Resource
{
    protected static ?string $viewPermission = null;

    protected static ?string $managePermission = null;

    protected static bool $isScopedToTenant = false;

    public static function getAuthorizationResponse(string|UnitEnum $action, ?Model $record = null): Response
    {
        $action = match (true) {
            $action instanceof BackedEnum => (string) $action->value,
            $action instanceof UnitEnum => $action->name,
            default => $action,
        };

        $user = Filament::auth()->user();

        $allowed = match ($action) {
            'viewAny', 'view' => Permissions::allows($user, static::$viewPermission),
            'create', 'update', 'reorder', 'replicate' => Permissions::allows($user, static::$managePermission),
            'delete', 'deleteAny', 'restore', 'restoreAny' => static::canBeDeleted($record) && LiteCrm::isSuperAdmin($user),
            default => false,
        };

        return $allowed ? Response::allow() : Response::deny();
    }

    /**
     * Override to protect particular records (e.g. system roles) from deletion.
     */
    protected static function canBeDeleted(?Model $record): bool
    {
        return true;
    }

    public static function getNavigationGroup(): ?string
    {
        return LiteCrmPlugin::settingsNavigationGroup();
    }
}
