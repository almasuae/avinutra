<?php

declare(strict_types=1);

namespace LiteCrm;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use LiteCrm\Contracts\CrmUser;
use LiteCrm\Models\Activity;
use LiteCrm\Models\Contact;
use LiteCrm\Models\Document;
use LiteCrm\Models\Organisation;
use LiteCrm\Models\Task;
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
     * Record models that activities, tasks and documents can be attached to.
     *
     * @return list<class-string<Model>>
     */
    public static function recordModels(): array
    {
        return [
            self::model(Organisation::class),
            self::model(Contact::class),
        ];
    }

    /**
     * Stable morph aliases, so stored types survive class renames and host subclasses.
     *
     * @return array<string, class-string<Model>>
     */
    public static function morphMap(): array
    {
        return [
            'crm_organisation' => self::model(Organisation::class),
            'crm_contact' => self::model(Contact::class),
            'crm_activity' => self::model(Activity::class),
            'crm_task' => self::model(Task::class),
            'crm_document' => self::model(Document::class),
        ];
    }

    /**
     * Active CRM users for select fields, as id => name.
     *
     * @return array<int|string, string>
     */
    public static function userOptions(): array
    {
        /** @var array<int|string, string> $options */
        $options = self::userModel()::query()
            ->whereHas('crmProfile', fn ($query) => $query->where('is_active', true))
            ->orderBy('name')
            ->pluck('name', (new (self::userModel()))->getKeyName())
            ->all();

        return $options;
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
