<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Pages;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\SimplePage;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Validation\Rules\Password;
use LiteCrm\Contracts\CrmUser;
use LiteCrm\LiteCrm;
use LiteCrm\Models\UserProfile;
use Livewire\Attributes\Locked;

/**
 * Reached through the signed link in the invitation e-mail. The user chooses a
 * password (minimum length from config) and is signed in.
 *
 * @property-read Schema $form
 */
class AcceptInvitation extends SimplePage
{
    use WithRateLimiting;

    #[Locked]
    public int|string|null $userId = null;

    #[Locked]
    public ?int $invitation = null;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(int|string $user): void
    {
        if (Filament::auth()->check()) {
            Filament::auth()->logout();
        }

        $this->userId = $user;
        $this->invitation = request()->integer('invitation');

        $this->form->fill(['email' => $this->resolveInvitedUser()->getAttribute('email')]);
    }

    public function accept(): void
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            Notification::make()
                ->title(__('lite-crm::users.invitation.page.throttled', ['seconds' => $exception->secondsUntilAvailable]))
                ->danger()
                ->send();

            return;
        }

        $user = $this->resolveInvitedUser();
        $data = $this->form->getState();

        $user->forceFill(['password' => $data['password']])->save();

        /** @var UserProfile $profile */
        $profile = $user->crmProfile()->firstOrFail();
        $profile->forceFill(['invitation_accepted_at' => Date::now()])->save();

        activity('crm')->causedBy($user)->performedOn($user)->event('invitation_accepted')->log('invitation_accepted');

        Filament::auth()->login($user);
        session()->regenerate();

        $this->redirect(Filament::getUrl());
    }

    protected function resolveInvitedUser(): Model&CrmUser
    {
        $user = LiteCrm::userModel()::query()->find($this->userId);

        /** @var UserProfile|null $profile */
        $profile = $user?->crmProfile()->first();

        $valid = $user instanceof CrmUser
            && $profile !== null
            && $profile->is_active
            && $profile->isInvitationPending()
            && $profile->invited_at?->getTimestamp() === $this->invitation
            && $profile->invited_at->copy()->addHours((int) config('lite-crm.auth.invitation_expiry_hours', 72))->isFuture();

        abort_unless($valid, 403, __('lite-crm::users.invitation.page.invalid'));

        return $user;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                TextInput::make('email')
                    ->label(__('lite-crm::users.fields.email'))
                    ->disabled()
                    ->dehydrated(false),
                TextInput::make('password')
                    ->label(__('lite-crm::users.invitation.page.password'))
                    ->password()
                    ->revealable()
                    ->autocomplete('new-password')
                    ->required()
                    ->rule(Password::min((int) config('lite-crm.auth.password_min_length', 12)))
                    ->same('passwordConfirmation')
                    ->helperText(__('lite-crm::users.invitation.page.password_help', ['min' => (int) config('lite-crm.auth.password_min_length', 12)])),
                TextInput::make('passwordConfirmation')
                    ->label(__('lite-crm::users.invitation.page.password_confirmation'))
                    ->password()
                    ->revealable()
                    ->autocomplete('new-password')
                    ->required()
                    ->dehydrated(false),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([$this->getFormContentComponent()]);
    }

    public function getFormContentComponent(): Component
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler('accept')
            ->footer([
                Actions::make([
                    Action::make('accept')
                        ->label(__('lite-crm::users.invitation.page.submit'))
                        ->submit('accept'),
                ])->fullWidth()->key('form-actions'),
            ]);
    }

    public function getTitle(): string|Htmlable
    {
        return __('lite-crm::users.invitation.page.title');
    }

    public function getHeading(): string|Htmlable|null
    {
        return __('lite-crm::users.invitation.page.heading', ['app' => config('app.name')]);
    }
}
