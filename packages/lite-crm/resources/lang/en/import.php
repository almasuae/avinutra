<?php

declare(strict_types=1);

return [
    'import' => 'Import',
    'export' => 'Export',
    'yes' => 'Yes',
    'no' => 'No',
    'update_existing' => 'Update existing records (matched by e-mail, or by name and city) instead of skipping them',
    'organisation_city' => 'Organisation city',
    'completed' => ':count row(s) completed.',
    'failed_rows' => ':count row(s) failed; download the list of failed rows to see why.',
    'duplicate_organisation' => '":name" already exists in this city (tick "Update existing records" to update it).',
    'duplicate_contact' => 'A contact with the e-mail :email already exists (tick "Update existing records" to update it).',
    'duplicate_product' => 'The product ":name" already exists (tick "Update existing records" to update it).',
    'not_allowed_to_update' => 'This record already exists and you are not allowed to change it.',
    'organisation_not_found' => 'No organisation called ":name" exists. Import organisations first.',
    'product_not_found' => 'No product called ":name" exists. Import products first.',
];
