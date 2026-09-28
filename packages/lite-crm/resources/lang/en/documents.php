<?php

declare(strict_types=1);

return [
    'label' => 'document',
    'plural' => 'Documents',
    'fields' => [
        'title' => 'Title',
        'type' => 'Type',
        'file' => 'File',
        'file_help' => 'PDF, Word, Excel, JPG or PNG, up to :size MB. Stored privately.',
        'issuer' => 'Issued by',
        'certificate_number' => 'Certificate number',
        'issued_on' => 'Issued on',
        'expires_on' => 'Expires on',
        'verified' => 'Verified',
        'verification_method' => 'How was it verified?',
        'verification_method_help' => 'For example: "Certificate number checked on the issuing body\'s public register on this date".',
        'confidential' => 'Confidential',
        'confidential_help' => 'Only you and users allowed to see confidential documents can open it.',
        'notes' => 'Notes',
    ],
    'filters' => [
        'expiring' => 'Expiring within :days days',
        'expired' => 'Expired',
    ],
    'actions' => [
        'upload' => 'Upload document',
        'download' => 'Download',
        'verify' => 'Mark verified',
    ],
    'notifications' => [
        'verified' => 'Document marked as verified',
    ],
];
