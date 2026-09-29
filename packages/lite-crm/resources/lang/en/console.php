<?php

declare(strict_types=1);

return [
    'install' => [
        'start' => 'Installing Lite CRM.',
        'config_published' => 'Published config/lite-crm.php',
        'config_kept' => 'Kept the existing config (use --force to overwrite)',
        'done' => 'Lite CRM is installed. Next steps:',
        'next' => [
            'user_model' => 'Add the InteractsWithCrm trait and the Filament MFA interfaces to your user model (see the package README).',
            'panel' => 'Register LiteCrmPlugin::make() on a Filament panel.',
            'admin' => 'Create the first admin: php artisan lite-crm:create-admin you@example.com',
            'cron' => 'Run the scheduler every minute from cron: php artisan schedule:run',
            'mail' => 'Configure SMTP in .env so invitations can be sent.',
        ],
    ],
    'create_admin' => [
        'invalid_email' => 'Enter a valid e-mail address.',
        'not_installed' => 'The roles do not exist yet. Run php artisan lite-crm:install (or php artisan db:seed) first.',
        'exists' => 'A user with the e-mail :email already exists.',
        'ask_name' => 'Full name',
        'name_required' => 'A name is required (use --name in non-interactive mode).',
        'needs_interaction' => 'A password can only be entered interactively. Use --invite to e-mail an invitation instead.',
        'ask_password' => 'Password (at least :min characters)',
        'ask_password_confirmation' => 'Confirm password',
        'invited' => 'Invitation sent to :email.',
        'created' => 'Admin :email created.',
        'mfa_notice' => 'Multi-factor authentication must be set up at the first sign-in.',
    ],
];
