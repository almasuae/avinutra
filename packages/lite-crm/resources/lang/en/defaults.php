<?php

declare(strict_types=1);

/*
 * Labels for the neutral lists seeded at installation. They are copied into the
 * database, where Admins can rename them.
 */
return [
    'lookups' => [
        'organisation_type' => [
            'customer' => 'Customer',
            'supplier' => 'Supplier',
            'partner' => 'Partner',
            'service_provider' => 'Service provider',
            'other' => 'Other',
        ],
        'organisation_status' => [
            'prospect' => 'Prospect',
            'active' => 'Active',
            'inactive' => 'Inactive',
        ],
        'activity_type' => [
            'call' => 'Call',
            'meeting' => 'Meeting',
            'email' => 'E-mail',
            'message' => 'Message',
            'visit' => 'Visit',
            'note' => 'Note',
        ],
        'document_type' => [
            'contract' => 'Contract',
            'certificate' => 'Certificate',
            'specification' => 'Specification',
            'company_profile' => 'Company profile',
            'other' => 'Other',
        ],
        'lost_reason' => [
            'price' => 'Price',
            'timing' => 'Timing',
            'competitor' => 'Chose a competitor',
            'requirements' => 'Requirements not met',
            'no_response' => 'No response',
            'other' => 'Other',
        ],
        'enquiry_type' => [
            'general' => 'General',
            'quotation' => 'Quotation',
            'partnership' => 'Partnership',
        ],
    ],
    'pipelines' => [
        'sales' => [
            'name' => 'Sales',
            'stages' => [
                'new' => 'New',
                'qualified' => 'Qualified',
                'proposal' => 'Proposal',
                'negotiation' => 'Negotiation',
                'won' => 'Won',
                'lost' => 'Lost',
            ],
        ],
    ],
];
