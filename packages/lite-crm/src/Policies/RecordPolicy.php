<?php

declare(strict_types=1);

namespace LiteCrm\Policies;

use Illuminate\Database\Eloquent\Model;
use LiteCrm\LiteCrm;
use LiteCrm\Support\Permissions;

/**
 * Base policy for record models that use HasVisibility.
 *
 * - viewAny / create: the module is enabled and the user holds "{module}.view" / ".create";
 * - view / update: additionally the record is visible to the user (own, assigned,
 *   territory, or "{module}.view_all");
 * - delete / restore: Admins only (they pass via Gate::before); deletion is soft;
 * - forceDelete: never, for anyone.
 */
abstract class RecordPolicy
{
    abstract protected function module(): string;

    public function viewAny(Model $user): bool
    {
        return $this->allows($user, 'view');
    }

    public function view(Model $user, Model $record): bool
    {
        return $this->allows($user, 'view') && $this->isVisible($user, $record);
    }

    public function create(Model $user): bool
    {
        return $this->allows($user, 'create');
    }

    public function update(Model $user, Model $record): bool
    {
        return $this->allows($user, 'update') && $this->isVisible($user, $record);
    }

    public function delete(Model $user, Model $record): bool
    {
        return false;
    }

    public function deleteAny(Model $user): bool
    {
        return false;
    }

    public function restore(Model $user, Model $record): bool
    {
        return false;
    }

    public function restoreAny(Model $user): bool
    {
        return false;
    }

    public function forceDelete(Model $user, Model $record): bool
    {
        return false;
    }

    public function forceDeleteAny(Model $user): bool
    {
        return false;
    }

    protected function allows(Model $user, string $ability): bool
    {
        return LiteCrm::isModuleEnabled($this->module())
            && Permissions::allows($user, $this->module().'.'.$ability);
    }

    protected function isVisible(Model $user, Model $record): bool
    {
        return method_exists($record, 'isVisibleTo') && $record->isVisibleTo($user);
    }
}
