<?php

declare(strict_types=1);

return [
    'label' => 'product',
    'plural' => 'Products',
    'no_specification' => 'No specification recorded.',
    'sections' => [
        'details' => 'Details',
        'handling' => 'Packaging, storage and shelf life',
    ],
    'fields' => [
        'name' => 'Name',
        'slug' => 'Web address name',
        'slug_help' => 'Used in the product\'s web address. Leave empty to create it from the name.',
        'category' => 'Category',
        'description' => 'Description',
        'specification' => 'Specification',
        'parameter' => 'Parameter',
        'value' => 'Value',
        'unit' => 'Unit',
        'packaging' => 'Packaging',
        'storage' => 'Storage',
        'shelf_life' => 'Shelf life',
        'availability' => 'Availability',
        'availability_help' => '"Available" means supply is secured; only users allowed to confirm that can choose it.',
        'publish_on_website' => 'Publish on the website',
        'published' => 'On website',
        'suppliers' => 'Suppliers',
        'supplier_notes' => 'Notes',
    ],
    'errors' => [
        'mark_available' => 'You are not allowed to mark products as available.',
        'publish' => 'You are not allowed to publish products on the website.',
    ],
];
