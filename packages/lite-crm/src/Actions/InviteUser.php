<?php

declare(strict_types=1);

namespace LiteCrm\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use LiteCrm\Contracts\CrmUser;
use LiteCrm\LiteCrm;
use LiteCrm\Models\UserProfile;
use LiteCrm\Notifications\UserInvitation;

/**
 * Creates a user with an unusable random password and e-mails a signed link
 * where they choose their own password. There is no public registration.
 */
class InviteUser
{
    /**
     * @param  array{name: string, email: string, roles?: list<string>, profile?: array<string, mixed>}  $data
     */
    public function handle(array $data, ?Model $inviter = null): Model&CrmUser
    {
        $user = DB::transaction(function () use ($data, $inviter): Model&CrmUser {
            $userModel = LiteCrm::userModel();
            $user = new $userModel;
            $user->forceFill([
                'name' => $data['name'],
                'email' => Str::lower($data['email']),
                'password' => Str::password(64),
            ])->save();

            /** @var UserProfile $profile */
            $profile = $user->crmProfile()->make($data['profile'] ?? []);
            $profile->forceFill(['invited_by' => $inviter?->getKey()]);
            $profile->save();

            $user->syncRoles($data['roles'] ?? []);

            return $user;
        });

        $this->send($user, $inviter);

        return $user;
    }

    /**
     * (Re)send the invitation. Each send invalidates earlier links.
     */
    public function send(Model&CrmUser $user, ?Model $inviter = null): void
    {
        /** @var UserProfile $profile */
        $profile = $user->crmProfile()->firstOrFail();

        $profile->forceFill(['invited_at' => Date::now(), 'invitation_accepted_at' => null])->save();

        $hours = (int) config('lite-crm.auth.invitation_expiry_hours', 72);

        $user->notify(new UserInvitation(
            url: static::url($user, $profile),
            expiresInHours: $hours,
            inviterName: $inviter?->getAttribute('name'),
        ));

        activity('crm')
            ->causedBy($inviter)
            ->performedOn($user)
            ->event('invited')
            ->log('invited');
    }

    public static function url(Model&CrmUser $user, UserProfile $profile): string
    {
        return URL::temporarySignedRoute(
            'filament.'.LiteCrm::panelId().'.lite-crm.invitation',
            Date::now()->addHours((int) config('lite-crm.auth.invitation_expiry_hours', 72)),
            [
                'user' => $user->getKey(),
                // Ties the link to this particular invitation, so resending invalidates older links.
                'invitation' => $profile->invited_at?->getTimestamp(),
            ],
        );
    }
}
