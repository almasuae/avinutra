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
        'enquiry_api' => 'Enquiry intake API',
        'role_labels_help' => 'The names users see for each role. Leave empty to use the default.',
    ],
    'enquiry_api' => [
        'description' => 'Lets other websites post enquiries to :path. Send the token as "Authorization: Bearer <token>". Only a hash of the token is stored.',
        'enabled' => 'The endpoint is switched on (LITE_CRM_ENQUIRY_API=true).',
        'disabled' => 'The endpoint is switched off. Set LITE_CRM_ENQUIRY_API=true in .env to use it.',
        'token_set' => 'A token was generated on :date.',
        'no_token' => 'No token has been generated: every request will be refused.',
        'generate' => 'Generate token',
        'rotate' => 'Replace token',
        'rotate_confirm' => 'The current token stops working immediately. The new token is shown once: copy it before closing the message.',
        'new_token' => 'New token (copy it now, it will not be shown again)',
        'revoke' => 'Revoke token',
        'revoked' => 'Token revoked',
    ],
    'fields' => [
        'mfa_required_for_all' => 'Require multi-factor authentication for every user',
        'mfa_required_for_all_help' => 'Admins must always use it. When this is on, every user sets it up at their next sign-in.',
    ],
];
