<?php

declare(strict_types=1);

namespace LiteCrm\Database\Seeders;

use Illuminate\Database\Seeder;
use LiteCrm\LiteCrm;
use LiteCrm\Support\Permissions;
use Spatie\Permission\PermissionRegistrar;

/**
 * Creates the permission catalogue and the default roles. Idempotent, and it
 * never undoes an Admin's changes: a role receives its default permissions when
 * the role is created, and afterwards only permissions that are new.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->forgetCachedPermissions();

        $permissionClass = $registrar->getPermissionClass();
        $roleClass = LiteCrm::roleModel();
        $guard = (string) config('auth.defaults.guard', 'web');

        $newPermissions = [];

        foreach (Permissions::all() as $name) {
            $permission = $permissionClass::query()->firstOrCreate(['name' => $name, 'guard_name' => $guard]);

            if ($permission->wasRecentlyCreated) {
                $newPermissions[] = $name;
            }
        }

        foreach (Permissions::ROLES as $name) {
            $role = $roleClass::query()->firstOrCreate(['name' => $name, 'guard_name' => $guard]);
            $defaults = Permissions::defaultsFor($name);

            $grant = $role->wasRecentlyCreated ? $defaults : array_values(array_intersect($defaults, $newPermissions));

            if ($grant !== []) {
                $role->givePermissionTo($grant);
            }
        }

        $registrar->forgetCachedPermissions();
    }
}
