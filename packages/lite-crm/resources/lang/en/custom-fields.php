<?php

declare(strict_types=1);

return [
    'label' => 'custom field',
    'plural' => 'Custom fields',
    'default_section' => 'Additional details',
    'entities' => [
        'organisation' => 'Organisation',
        'contact' => 'Contact',
        'opportunity' => 'Opportunity',
        'product' => 'Product',
        'trial' => 'Trial',
        'sample' => 'Sample',
    ],
    'types' => [
        'text' => 'Text',
        'textarea' => 'Long text',
        'number' => 'Whole number',
        'decimal' => 'Decimal number',
        'currency' => 'Amount (base currency)',
        'date' => 'Date',
        'boolean' => 'Yes / no',
        'select' => 'Choice (one)',
        'multiselect' => 'Choice (several)',
        'percentage' => 'Percentage',
        'url' => 'Web address',
    ],
    'sections' => [
        'behaviour' => 'Behaviour',
    ],
    'fields' => [
        'entity' => 'Record type',
        'type' => 'Field type',
        'label' => 'Label',
        'key' => 'Key',
        'key_help' => 'Lower case letters, digits and "_", starting with a letter. It cannot be changed later.',
        'section' => 'Section',
        'section_help' => 'Fields with the same section are shown together. Leave empty for "Additional details".',
        'help_text' => 'Help text',
        'options' => 'Choices',
        'option_value' => 'Value',
        'option_label' => 'Label',
        'visible_for_types' => 'Only for these types',
        'visible_for_types_help' => 'Leave empty to show the field on every record.',
        'required' => 'Required',
        'show_in_table' => 'Show in tables',
        'filterable' => 'Filterable',
        'filterable_help' => 'Available for choice and yes / no fields.',
        'is_active' => 'Active',
        'sort' => 'Order',
    ],
];
