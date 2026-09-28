<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Resources\Users\Pages;

use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use LiteCrm\Actions\InviteUser;
use LiteCrm\Filament\Resources\Users\UserResource;

class ManageUsers extends ManageRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('lite-crm::users.actions.invite'))
                ->icon(Heroicon::OutlinedEnvelope)
                ->modalHeading(__('lite-crm::users.actions.invite'))
                ->successNotificationTitle(__('lite-crm::users.notifications.invitation_sent'))
                ->using(function (array $data): Model {
                    /** @var Model|null $inviter */
                    $inviter = Filament::auth()->user();

                    return app(InviteUser::class)->handle([
                        'name' => $data['name'],
                        'email' => $data['email'],
                        'roles' => $data['roles'] ?? [],
                        'profile' => $data['profile'] ?? [],
                    ], $inviter);
                }),
        ];
    }
}
