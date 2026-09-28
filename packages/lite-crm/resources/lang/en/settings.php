<?php

declare(strict_types=1);

return [
    'navigation' => 'Settings',
    'title' => 'CRM settings',
    'save' => 'Save',
    'saved' => 'Settings saved',
    'sections' => [
        'security' => 'Security',
        'role_labels' => 'Role labels',
        'role_labels_help' => 'The names users see for each role. Leave empty to use the default.',
    ],
    'fields' => [
        'mfa_required_for_all' => 'Require multi-factor authentication for every user',
        'mfa_required_for_all_help' => 'Admins must always use it. When this is on, every user sets it up at their next sign-in.',
    ],
];
