<?php

declare(strict_types=1);

return [
    'label' => 'user',
    'plural' => 'Users',
    'sections' => [
        'profile' => 'Profile',
    ],
    'fields' => [
        'name' => 'Name',
        'email' => 'E-mail',
        'roles' => 'Roles',
        'job_title' => 'Job title',
        'city' => 'City',
        'country' => 'Country',
        'time_zone' => 'Time zone',
        'phone' => 'Phone',
        'whatsapp' => 'WhatsApp',
        'territory' => 'Territory',
        'receives_digest' => 'Receive the daily digest e-mail',
        'is_active' => 'Active',
        'status' => 'Status',
        'last_login_at' => 'Last login',
    ],
    'status' => [
        'active' => 'Active',
        'invited' => 'Invited',
        'inactive' => 'Deactivated',
    ],
    'actions' => [
        'invite' => 'Invite user',
        'resend_invitation' => 'Resend invitation',
    ],
    'notifications' => [
        'invitation_sent' => 'Invitation sent',
    ],
    'invitation' => [
        'mail' => [
            'subject' => 'Your invitation to :app',
            'greeting' => 'Hello,',
            'intro' => 'You have been invited to join the :app team workspace.',
            'intro_by' => ':name has invited you to join the :app team workspace.',
            'action' => 'Choose your password',
            'expiry' => 'This link expires in :hours hours.',
            'ignore' => 'If you were not expecting this invitation, you can ignore this e-mail.',
        ],
        'page' => [
            'title' => 'Accept invitation',
            'heading' => 'Welcome to :app',
            'password' => 'Password',
            'password_confirmation' => 'Confirm password',
            'password_help' => 'At least :min characters.',
            'submit' => 'Set password and sign in',
            'invalid' => 'This invitation link is invalid, expired or already used. Ask an administrator to send a new one.',
            'throttled' => 'Too many attempts. Try again in :seconds seconds.',
        ],
    ],
];
