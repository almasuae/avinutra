<?php

declare(strict_types=1);

return [
    'task_status' => [
        'open' => 'Open',
        'in_progress' => 'In progress',
        'done' => 'Done',
        'cancelled' => 'Cancelled',
    ],
    'enquiry_status' => [
        'new' => 'New',
        'assigned' => 'Assigned',
        'in_progress' => 'In progress',
        'converted' => 'Converted',
        'closed' => 'Closed',
        'spam' => 'Spam',
    ],
    'product_availability' => [
        'available' => 'Available',
        'on_request' => 'Sourced on request',
        'information' => 'Information only',
    ],
    'trial_status' => [
        'planned' => 'Planned',
        'running' => 'Running',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ],
    'quotation_status' => [
        'draft' => 'Draft',
        'sent' => 'Sent',
        'accepted' => 'Accepted',
        'rejected' => 'Rejected',
        'expired' => 'Expired',
    ],
    'price_source_type' => [
        'supplier_offer' => 'Supplier offer',
        'customs_derived' => 'Derived from customs data',
        'published_assessment' => 'Published price assessment',
        'market_report' => 'Market report',
    ],
    'price_basis' => [
        'EXW' => 'EXW',
        'FOB' => 'FOB',
        'CFR' => 'CFR',
        'CIF' => 'CIF',
        'landed' => 'Landed',
    ],
    'price_confidence' => [
        'verified' => 'Verified',
        'reported' => 'Reported',
        'unconfirmed' => 'Unconfirmed',
    ],
    'incoterm' => [
        'EXW' => 'EXW — Ex Works',
        'FCA' => 'FCA — Free Carrier',
        'CPT' => 'CPT — Carriage Paid To',
        'CIP' => 'CIP — Carriage and Insurance Paid To',
        'DAP' => 'DAP — Delivered at Place',
        'DPU' => 'DPU — Delivered at Place Unloaded',
        'DDP' => 'DDP — Delivered Duty Paid',
        'FAS' => 'FAS — Free Alongside Ship',
        'FOB' => 'FOB — Free on Board',
        'CFR' => 'CFR — Cost and Freight',
        'CIF' => 'CIF — Cost, Insurance and Freight',
    ],
    'task_priority' => [
        'low' => 'Low',
        'normal' => 'Normal',
        'high' => 'High',
        'urgent' => 'Urgent',
    ],
    'preferred_channel' => [
        'email' => 'E-mail',
        'phone' => 'Phone',
        'whatsapp' => 'WhatsApp',
        'in_person' => 'In person',
    ],
    'consent_basis' => [
        'consent' => 'Consent',
        'contract' => 'Contract',
        'legitimate_interest' => 'Legitimate interest',
        'other' => 'Other',
    ],
];
