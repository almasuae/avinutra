<?php

declare(strict_types=1);

namespace LiteCrm\Tests\Fixtures;

use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use LiteCrm\Concerns\InteractsWithCrm;
use LiteCrm\Contracts\CrmUser;

/**
 * A host user model set up as the README describes.
 */
class User extends Authenticatable implements CrmUser, FilamentUser, HasAppAuthentication, HasAppAuthenticationRecovery
{
    use InteractsWithCrm;
    use Notifiable;

    protected $table = 'users';

    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password', 'remember_token'];

    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'crm' && $this->canAccessCrm();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }
}
