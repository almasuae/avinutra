<?php

declare(strict_types=1);

return [
    'label' => 'trial',
    'plural' => 'Trials',
    'sections' => [
        'trial' => 'Trial',
        'consent' => 'Publication',
        'consent_help' => 'Results may be published only with the customer\'s written consent, recorded here with its date. Attach the signed consent under Documents.',
    ],
    'fields' => [
        'name' => 'Name',
        'status' => 'Status',
        'start_date' => 'Start',
        'end_date' => 'End',
        'protocol' => 'Protocol',
        'protocol_help' => 'A summary; attach the full protocol under Documents.',
        'result_summary' => 'Result summary',
        'consent_to_publish' => 'Customer consents to publication',
        'consent_short' => 'May publish',
        'consent_date' => 'Consent date',
    ],
];
