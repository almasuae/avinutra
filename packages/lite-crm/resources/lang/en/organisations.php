<?php

declare(strict_types=1);

return [
    'label' => 'organisation',
    'plural' => 'Organisations',
    'sections' => [
        'details' => 'Details',
        'contact' => 'Contact details and location',
        'public_naming' => 'Naming on public websites',
        'public_naming_help' => 'Record written permission before this organisation is named, or its logo shown, on any public website.',
    ],
    'fields' => [
        'name' => 'Name',
        'type' => 'Type',
        'status' => 'Status',
        'country' => 'Country',
        'region' => 'Region / province',
        'city' => 'City',
        'address' => 'Address',
        'website' => 'Website',
        'phone' => 'Phone',
        'email' => 'E-mail',
        'territory' => 'Territory',
        'source' => 'Source',
        'notes' => 'Notes',
        'permission_to_name_publicly' => 'Written permission to name publicly',
        'permission_granted_on' => 'Permission date',
        'permission_document' => 'Evidence',
        'permission_document_help' => 'A document attached to this organisation (upload it under Documents first).',
    ],
];
