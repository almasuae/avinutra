<?php

declare(strict_types=1);

namespace LiteCrm\Support;

use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;
use LiteCrm\LiteCrm;
use Spatie\Permission\Traits\HasRoles;

/**
 * The permission catalogue and the default permissions of each role (v5 §D1).
 *
 * Permission names are "{area}.{ability}", e.g. "contacts.update" or "users.manage".
 * Admins hold every permission and also pass every check (see LiteCrm::isSuperAdmin).
 * The defaults are applied when a role or permission is first created; after
 * that, Admins adjust them in CRM › Settings › Roles.
 */
class Permissions
{
    public const ROLES = ['admin', 'manager', 'commercial', 'specialist', 'partner', 'viewer'];

    /** Modules that hold records. */
    public const RECORD_MODULES = [
        'organisations', 'contacts', 'enquiries', 'opportunities', 'activities', 'tasks', 'products',
        'samples', 'trials', 'quotations', 'price_log', 'documents', 'announcements', 'decisions',
    ];

    /** "view_all" sees every record; without it a user sees only their own or assigned records. */
    public const RECORD_ABILITIES = ['view', 'view_all', 'create', 'update', 'delete', 'export'];

    public const SYSTEM_PERMISSIONS = [
        'dashboard.view',
        'users.view',
        'users.manage',
        'roles.manage',
        'settings.manage',
        'lookups.view',
        'lookups.manage',
        'custom_fields.manage',
        'audit_log.view',
        'import.run',
        'website.manage',
        'technical_content.approve',
        // Confidential documents are otherwise visible only to their owner.
        'documents.view_confidential',
        // Setting a product to "available" means supply is secured.
        'products.mark_available',
        // Filter the dashboard by user, territory and period.
        'dashboard.filter',
    ];

    /**
     * A readable label, e.g. "Documents: view confidential" for documents.view_confidential.
     */
    public static function label(string $permission): string
    {
        [$area, $ability] = array_pad(explode('.', $permission, 2), 2, '');

        return static::areaLabel($area).': '.(Lang::has("lite-crm::permissions.abilities.{$ability}")
            ? (string) __("lite-crm::permissions.abilities.{$ability}")
            : str_replace('_', ' ', $ability));
    }

    public static function areaLabel(string $area): string
    {
        return Lang::has("lite-crm::permissions.areas.{$area}")
            ? (string) __("lite-crm::permissions.areas.{$area}")
            : Str::headline($area);
    }

    /**
     * Permission names grouped by area (the part before the first dot), groups
     * sorted by their label.
     *
     * @param  iterable<string>  $names
     * @return array<string, array{label: string, permissions: array<string, string>}> area => label and name => label
     */
    public static function grouped(iterable $names): array
    {
        $groups = [];

        foreach ($names as $name) {
            $area = explode('.', $name, 2)[0];
            $groups[$area]['label'] ??= static::areaLabel($area);
            $groups[$area]['permissions'][$name] = static::label($name);
        }

        uasort($groups, fn (array $a, array $b): int => strcmp($a['label'], $b['label']));

        return $groups;
    }

    /**
     * Every permission name.
     *
     * @return list<string>
     */
    public static function all(): array
    {
        $names = [];

        foreach (self::RECORD_MODULES as $module) {
            foreach (self::RECORD_ABILITIES as $ability) {
                $names[] = "{$module}.{$ability}";
            }
        }

        return array_merge($names, self::SYSTEM_PERMISSIONS);
    }

    /**
     * The default permissions of a role.
     *
     * @return list<string>
     */
    public static function defaultsFor(string $role): array
    {
        $commercialModules = ['organisations', 'contacts', 'enquiries', 'opportunities', 'activities', 'tasks', 'samples', 'quotations', 'price_log'];
        $partnerModules = ['organisations', 'contacts', 'enquiries', 'opportunities', 'activities', 'tasks', 'samples', 'quotations'];
        $everyone = ['dashboard.view', 'lookups.view'];

        $permissions = match ($role) {
            'admin' => self::all(),

            'manager' => array_merge(
                self::abilities(self::RECORD_MODULES, ['view', 'view_all', 'create', 'update', 'export']),
                $everyone,
                ['users.view', 'audit_log.view', 'import.run', 'website.manage', 'documents.view_confidential', 'products.mark_available', 'dashboard.filter'],
            ),

            'commercial' => array_merge(
                self::abilities($commercialModules, ['view', 'view_all', 'create', 'update', 'export']),
                self::abilities(['documents'], ['view', 'view_all', 'create', 'update']),
                self::abilities(['products', 'announcements'], ['view', 'view_all']),
                self::abilities(['decisions'], ['view', 'view_all', 'create']),
                $everyone,
            ),

            'specialist' => array_merge(
                self::defaultsFor('commercial'),
                self::abilities(['trials'], ['view', 'view_all', 'create', 'update', 'export']),
                self::abilities(['products'], ['create', 'update']),
                ['technical_content.approve', 'documents.view_confidential'],
            ),

            // Own, assigned or territory records only; no exports, no price log.
            'partner' => array_merge(
                self::abilities($partnerModules, ['view', 'create', 'update']),
                self::abilities(['documents'], ['view', 'create']),
                self::abilities(['products', 'announcements', 'decisions'], ['view', 'view_all']),
                $everyone,
            ),

            'viewer' => array_merge(
                self::abilities(self::RECORD_MODULES, ['view', 'view_all']),
                $everyone,
            ),

            default => [],
        };

        return array_values(array_unique($permissions));
    }

    /**
     * Whether a user holds a permission. Super admins hold every permission.
     */
    public static function allows(mixed $user, ?string $permission): bool
    {
        if ($permission === null || ! is_object($user)) {
            return false;
        }

        if (LiteCrm::isSuperAdmin($user)) {
            return true;
        }

        if (! in_array(HasRoles::class, class_uses_recursive($user), true)) {
            return false;
        }

        return $user->checkPermissionTo($permission);
    }

    /**
     * @param  list<string>  $modules
     * @param  list<string>  $abilities
     * @return list<string>
     */
    protected static function abilities(array $modules, array $abilities): array
    {
        $names = [];

        foreach ($modules as $module) {
            foreach ($abilities as $ability) {
                $names[] = "{$module}.{$ability}";
            }
        }

        return $names;
    }
}
