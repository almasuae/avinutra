<?php

declare(strict_types=1);

namespace LiteCrm\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use LiteCrm\Models\UserProfile;

/**
 * What the package needs from the host's user model. Implemented by the
 * InteractsWithCrm trait (which includes spatie/laravel-permission's HasRoles)
 * together with Laravel's Notifiable.
 */
interface CrmUser extends Authenticatable
{
    /**
     * @return HasOne<UserProfile, covariant \Illuminate\Database\Eloquent\Model>
     */
    public function crmProfile(): HasOne;

    public function getCrmProfile(): UserProfile;

    public function canAccessCrm(): bool;

    public function crmTimezone(): string;

    public function requiresMultiFactorAuthentication(): bool;

    /**
     * @return BelongsToMany<Model, covariant \Illuminate\Database\Eloquent\Model>
     */
    public function roles(): BelongsToMany;

    /**
     * @param  mixed  ...$roles
     */
    public function assignRole(...$roles): static;

    /**
     * @param  mixed  ...$roles
     */
    public function syncRoles(...$roles): static;

    /**
     * @param  mixed  $roles
     */
    public function hasRole($roles, ?string $guard = null): bool;

    /**
     * @param  mixed  ...$roles
     */
    public function hasAnyRole(...$roles): bool;

    /**
     * @param  mixed  $instance
     */
    public function notify($instance);
}
