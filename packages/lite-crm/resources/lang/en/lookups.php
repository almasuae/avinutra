<?php

declare(strict_types=1);

return [
    'label' => 'list entry',
    'plural' => 'Lists',
    'types' => [
        'organisation_type' => 'Organisation types',
        'organisation_status' => 'Organisation statuses',
        'activity_type' => 'Activity types',
        'document_type' => 'Document types',
        'lost_reason' => 'Lost reasons',
        'territory' => 'Territories',
        'enquiry_type' => 'Enquiry types',
        'product_category' => 'Product categories',
    ],
    'fields' => [
        'type' => 'List',
        'label' => 'Label',
        'key' => 'Key',
        'key_help' => 'A short, permanent identifier (lower case, digits, "_", "-" or "."). It cannot be changed later.',
        'sort' => 'Order',
        'is_active' => 'Active',
        'mailbox' => 'Mailbox',
        'mailbox_help' => 'New enquiries of this type are also e-mailed here.',
    ],
];
