<?php

declare(strict_types=1);

namespace LiteCrm\Concerns;

use Illuminate\Database\Eloquent\Relations\HasOne;
use LiteCrm\LiteCrm;
use LiteCrm\Models\UserProfile;
use LiteCrm\Support\CrmSettings;
use SensitiveParameter;
use Spatie\Permission\Traits\HasRoles;

/**
 * Add to the host's user model, together with the Filament interfaces
 * FilamentUser, HasAppAuthentication and HasAppAuthenticationRecovery:
 *
 *     class User extends Authenticatable implements FilamentUser, HasAppAuthentication, HasAppAuthenticationRecovery
 *     {
 *         use InteractsWithCrm;
 *
 *         public function canAccessPanel(Panel $panel): bool
 *         {
 *             return $panel->getId() === 'crm' && $this->canAccessCrm();
 *         }
 *     }
 *
 * CRM data (profile, MFA secrets) is stored in crm_user_profiles, so the host's
 * users table is never changed.
 */
trait InteractsWithCrm
{
    use HasRoles;

    /**
     * @return HasOne<UserProfile, $this>
     */
    public function crmProfile(): HasOne
    {
        return $this->hasOne(LiteCrm::model(UserProfile::class), 'user_id');
    }

    /**
     * The saved profile, or a new unsaved one with defaults.
     */
    public function getCrmProfile(): UserProfile
    {
        /** @var UserProfile|null $profile */
        $profile = $this->crmProfile;

        if ($profile !== null) {
            return $profile;
        }

        /** @var UserProfile $profile */
        $profile = $this->crmProfile()->make(['time_zone' => config('app.timezone', 'UTC')]);

        return $profile;
    }

    /**
     * Invite-only access: the user needs an active CRM profile and at least one role.
     */
    public function canAccessCrm(): bool
    {
        /** @var UserProfile|null $profile */
        $profile = $this->crmProfile;

        return $profile !== null
            && $profile->is_active
            && ! $profile->isInvitationPending()
            && $this->roles()->exists();
    }

    public function crmTimezone(): string
    {
        /** @var UserProfile|null $profile */
        $profile = $this->crmProfile;

        return $profile->time_zone ?? (string) config('app.timezone', 'UTC');
    }

    public function requiresMultiFactorAuthentication(): bool
    {
        /** @var list<string> $roles */
        $roles = config('lite-crm.auth.mfa_required_roles', []);

        return app(CrmSettings::class)->isMfaRequiredForAll()
            || ($roles !== [] && $this->hasAnyRole($roles));
    }

    public function getAppAuthenticationSecret(): ?string
    {
        /** @var UserProfile|null $profile */
        $profile = $this->crmProfile;

        return $profile?->app_authentication_secret;
    }

    public function saveAppAuthenticationSecret(#[SensitiveParameter] ?string $secret): void
    {
        $profile = $this->getCrmProfile();
        $profile->app_authentication_secret = $secret;
        $profile->save();

        $this->setRelation('crmProfile', $profile);
    }

    public function getAppAuthenticationHolderName(): string
    {
        return (string) $this->getAttribute('email');
    }

    /**
     * @return ?array<string>
     */
    public function getAppAuthenticationRecoveryCodes(): ?array
    {
        /** @var UserProfile|null $profile */
        $profile = $this->crmProfile;

        return $profile?->app_authentication_recovery_codes;
    }

    /**
     * @param  ?array<string>  $codes
     */
    public function saveAppAuthenticationRecoveryCodes(#[SensitiveParameter] ?array $codes): void
    {
        $profile = $this->getCrmProfile();
        $profile->app_authentication_recovery_codes = $codes;
        $profile->save();

        $this->setRelation('crmProfile', $profile);
    }
}
