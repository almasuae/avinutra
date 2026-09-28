<?php

declare(strict_types=1);

namespace LiteCrm\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use LiteCrm\Actions\InviteUser;
use LiteCrm\LiteCrm;
use LiteCrm\Models\UserProfile;

/**
 * The only way to create the first admin: there are no seeded users and no
 * default passwords.
 */
class CreateAdminCommand extends Command
{
    protected $signature = 'lite-crm:create-admin
        {email : E-mail address of the admin}
        {--name= : Full name}
        {--invite : E-mail an invitation link instead of setting a password now}';

    protected $description = 'Create a CRM admin (prompts for a password, or sends an invitation with --invite)';

    public function handle(InviteUser $inviteUser): int
    {
        $email = Str::lower(trim((string) $this->argument('email')));

        if (Validator::make(['email' => $email], ['email' => ['required', 'email']])->fails()) {
            $this->components->error(__('lite-crm::console.create_admin.invalid_email'));

            return self::FAILURE;
        }

        $adminRole = LiteCrm::superAdminRole();

        if (! LiteCrm::roleModel()::query()->where('name', $adminRole)->exists()) {
            $this->components->error(__('lite-crm::console.create_admin.not_installed'));

            return self::FAILURE;
        }

        $userModel = LiteCrm::userModel();

        if ($userModel::query()->where('email', $email)->exists()) {
            $this->components->error(__('lite-crm::console.create_admin.exists', ['email' => $email]));

            return self::FAILURE;
        }

        $name = (string) ($this->option('name') ?: ($this->input->isInteractive() ? $this->ask(__('lite-crm::console.create_admin.ask_name')) : ''));

        if (trim($name) === '') {
            $this->components->error(__('lite-crm::console.create_admin.name_required'));

            return self::FAILURE;
        }

        if ($this->option('invite')) {
            $inviteUser->handle(['name' => $name, 'email' => $email, 'roles' => [$adminRole]]);
            $this->components->info(__('lite-crm::console.create_admin.invited', ['email' => $email]));

            return self::SUCCESS;
        }

        if (! $this->input->isInteractive()) {
            $this->components->error(__('lite-crm::console.create_admin.needs_interaction'));

            return self::FAILURE;
        }

        $password = $this->askForPassword();

        if ($password === null) {
            return self::FAILURE;
        }

        DB::transaction(function () use ($userModel, $name, $email, $password, $adminRole): void {
            $user = new $userModel;
            $user->forceFill(['name' => $name, 'email' => $email, 'password' => $password])->save();

            /** @var UserProfile $profile */
            $profile = $user->crmProfile()->make(['time_zone' => config('app.timezone', 'UTC')]);
            $profile->save();

            $user->assignRole($adminRole);

            activity('crm')->performedOn($user)->event('admin_created')->log('admin_created');
        });

        $this->components->info(__('lite-crm::console.create_admin.created', ['email' => $email]));
        $this->components->warn(__('lite-crm::console.create_admin.mfa_notice'));

        return self::SUCCESS;
    }

    protected function askForPassword(): ?string
    {
        $min = (int) config('lite-crm.auth.password_min_length', 12);

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $password = (string) $this->secret(__('lite-crm::console.create_admin.ask_password', ['min' => $min]));
            $confirmation = (string) $this->secret(__('lite-crm::console.create_admin.ask_password_confirmation'));

            $validator = Validator::make(
                ['password' => $password, 'password_confirmation' => $confirmation],
                ['password' => ['required', 'confirmed', Password::min($min)]],
            );

            if ($validator->passes()) {
                return $password;
            }

            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }
        }

        return null;
    }
}
