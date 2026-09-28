<?php

declare(strict_types=1);

namespace LiteCrm;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use LiteCrm\Contracts\CrmUser;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Traits\HasRoles;

/**
 * Entry point for host applications and for the package itself.
 */
class LiteCrm
{
    protected static string $panelId = 'crm';

    /**
     * The class to use for a package model: the host's subclass when one is
     * mapped in config('lite-crm.models'), otherwise the package model.
     *
     * @template TModel of Model
     *
     * @param  class-string<TModel>  $model
     * @return class-string<TModel>
     */
    public static function model(string $model): string
    {
        $class = config("lite-crm.models.{$model}");

        if ($class === null) {
            return $model;
        }

        if (! is_string($class) || ! is_a($class, $model, true)) {
            throw new InvalidArgumentException("lite-crm.models: [{$model}] must map to a subclass of itself.");
        }

        return $class;
    }

    /**
     * @return class-string<Model&CrmUser>
     */
    public static function userModel(): string
    {
        $class = config('lite-crm.user_model');

        if (! is_string($class) || ! is_a($class, Model::class, true) || ! is_a($class, CrmUser::class, true)) {
            throw new InvalidArgumentException('lite-crm.user_model must be an Eloquent model implementing '.CrmUser::class.'.');
        }

        return $class;
    }

    /**
     * @return class-string<Role>
     */
    public static function roleModel(): string
    {
        $class = app(PermissionRegistrar::class)->getRoleClass();

        if (! is_a($class, Role::class, true)) {
            throw new InvalidArgumentException('permission.models.role must extend '.Role::class.'.');
        }

        return $class;
    }

    /**
     * The id of the Filament panel the plugin is registered on.
     */
    public static function panelId(): string
    {
        return static::$panelId;
    }

    public static function setPanelId(string $panelId): void
    {
        static::$panelId = $panelId;
    }

    public static function table(string $name): string
    {
        return config('lite-crm.table_prefix', 'crm_').$name;
    }

    public static function isModuleEnabled(string $module): bool
    {
        return (bool) config("lite-crm.modules.{$module}", false);
    }

    public static function superAdminRole(): string
    {
        return (string) config('lite-crm.auth.super_admin_role', 'admin');
    }

    public static function isSuperAdmin(mixed $user): bool
    {
        return is_object($user)
            && in_array(HasRoles::class, class_uses_recursive($user), true)
            && $user->hasRole(self::superAdminRole());
    }
}
