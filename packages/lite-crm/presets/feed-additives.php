<?php

declare(strict_types=1);

/*
 * Preset for a poultry-feed nutrition and feed-additive supply business
 * (v5 §D6). This is the only file in the package where industry terms may
 * appear. Load it with:  php artisan lite-crm:preset feed-additives
 *
 * Stage probabilities are starting values for the team to adjust in
 * CRM settings › Pipelines; they are internal and never shown publicly.
 */

$species = ['broiler' => 'Broiler', 'layer' => 'Layer', 'breeder' => 'Breeder', 'other' => 'Other'];
$certification = ['certified' => 'Certified', 'in_progress' => 'In progress', 'none' => 'None', 'unknown' => 'Unknown'];

return [
    'name' => 'Feed additives',
    'description' => 'Poultry-feed nutrition consulting and feed-additive supply.',

    'lookups' => [
        'organisation_type' => [
            'replace' => true,
            'items' => [
                'feed_mill' => 'Feed mill',
                'integrator' => 'Integrator / poultry producer',
                'manufacturer_supplier' => 'Manufacturer / supplier',
                'trader_distributor' => 'Trader / distributor',
                'service_provider' => 'Service provider',
                'bank_finance' => 'Bank / finance',
                'other' => 'Other',
            ],
        ],
        'document_type' => [
            'replace' => true,
            'items' => [
                'tds' => 'TDS',
                'sds' => 'SDS',
                'coa' => 'COA',
                'specification' => 'Specification',
                'fami_qs' => 'FAMI-QS',
                'gmp_plus' => 'GMP+',
                'iso' => 'ISO',
                'halal' => 'Halal',
                'certificate_of_origin' => 'Certificate of origin',
                'registration' => 'Registration',
                'agreement' => 'Agreement',
                'nda' => 'NDA',
                'company_profile' => 'Company profile',
            ],
        ],
        'enquiry_type' => [
            'replace' => true,
            'items' => [
                'general' => 'General',
                'ask_nutritionist' => 'Ask a nutritionist',
                'sourcing_request' => 'Sourcing request',
                'quotation' => 'Quotation',
                'sample' => 'Sample',
                'document' => 'Document request',
                'supplier_application' => 'Supplier application',
            ],
        ],
        'territory' => [
            'items' => [
                'pk_punjab' => 'PK-Punjab',
                'pk_sindh' => 'PK-Sindh',
                'pk_kp' => 'PK-KP',
                'pk_balochistan' => 'PK-Balochistan',
                'pk_ict' => 'PK-ICT',
                'sg' => 'SG',
                'cn' => 'CN',
                'other' => 'Other',
            ],
        ],
        'product_category' => [
            'items' => [
                'amino_acids' => 'Amino acids',
                'enzymes' => 'Enzymes',
                'vitamins_minerals' => 'Vitamins & minerals',
                'mycotoxin_management' => 'Mycotoxin management',
                'gut_health' => 'Gut health',
                'specialty_additives' => 'Specialty additives',
            ],
        ],
    ],

    'pipelines' => [
        // The neutral "Sales" pipeline is switched off while it holds no opportunities.
        'replace' => true,
        'items' => [
            'sales_feed_mills' => [
                'name' => 'Sales (feed mills)',
                'stages' => [
                    'new' => ['New', 10],
                    'qualified' => ['Qualified', 20],
                    'sample_sent' => ['Sample sent', 30],
                    'trial' => ['Trial', 45],
                    'quotation' => ['Quotation', 60],
                    'negotiation' => ['Negotiation', 75],
                    'won' => ['Won', 100, 'won'],
                    'lost' => ['Lost', 0, 'lost'],
                ],
            ],
            'supply_partnership' => [
                'name' => 'Supply partnership (manufacturers)',
                // Not for Partners (v5 §D1: "no supplier pipeline").
                'visible_to_roles' => ['admin', 'manager', 'commercial', 'specialist', 'viewer'],
                'stages' => [
                    'identified' => ['Identified', 5],
                    'contacted' => ['Contacted', 10],
                    'documents_received' => ['Documents received', 25],
                    'due_diligence' => ['Due diligence', 40],
                    'commercial_terms' => ['Commercial terms', 60],
                    'agreement_signed' => ['Agreement signed', 90],
                    'active' => ['Active', 100, 'won'],
                    'dropped' => ['Dropped', 0, 'lost'],
                ],
            ],
        ],
    ],

    'custom_fields' => [
        // Organisation: feed mills.
        ['entity' => 'organisation', 'key' => 'feed_production_mt_month', 'label' => 'Feed production (MT/month)', 'type' => 'decimal', 'section' => 'Feed mill profile', 'visible_for_types' => ['feed_mill'], 'show_in_table' => true],
        ['entity' => 'organisation', 'key' => 'species_broiler_pct', 'label' => 'Species mix: broiler (%)', 'type' => 'percentage', 'section' => 'Feed mill profile', 'visible_for_types' => ['feed_mill']],
        ['entity' => 'organisation', 'key' => 'species_layer_pct', 'label' => 'Species mix: layer (%)', 'type' => 'percentage', 'section' => 'Feed mill profile', 'visible_for_types' => ['feed_mill']],
        ['entity' => 'organisation', 'key' => 'species_breeder_pct', 'label' => 'Species mix: breeder (%)', 'type' => 'percentage', 'section' => 'Feed mill profile', 'visible_for_types' => ['feed_mill']],
        ['entity' => 'organisation', 'key' => 'methionine_use_t_year', 'label' => 'Methionine use (t/yr)', 'type' => 'decimal', 'section' => 'Feed mill profile', 'visible_for_types' => ['feed_mill'], 'show_in_table' => true],
        ['entity' => 'organisation', 'key' => 'current_suppliers', 'label' => 'Current suppliers', 'type' => 'textarea', 'section' => 'Feed mill profile', 'visible_for_types' => ['feed_mill']],
        ['entity' => 'organisation', 'key' => 'import_history_notes', 'label' => 'Import history notes', 'type' => 'textarea', 'section' => 'Feed mill profile', 'visible_for_types' => ['feed_mill']],

        // Organisation: manufacturers and suppliers.
        ['entity' => 'organisation', 'key' => 'products_supplied', 'label' => 'Products supplied', 'type' => 'textarea', 'section' => 'Manufacturer profile', 'visible_for_types' => ['manufacturer_supplier']],
        ['entity' => 'organisation', 'key' => 'plant_locations', 'label' => 'Plant locations', 'type' => 'textarea', 'section' => 'Manufacturer profile', 'visible_for_types' => ['manufacturer_supplier']],
        ['entity' => 'organisation', 'key' => 'capacity_kt_year', 'label' => 'Capacity (kt/yr)', 'type' => 'decimal', 'section' => 'Manufacturer profile', 'visible_for_types' => ['manufacturer_supplier']],
        ['entity' => 'organisation', 'key' => 'pakistan_presence', 'label' => 'Pakistan presence', 'type' => 'select', 'section' => 'Manufacturer profile', 'visible_for_types' => ['manufacturer_supplier'], 'filterable' => true, 'show_in_table' => true,
            'options' => ['a' => 'A — direct', 'b' => 'B — via a trader', 'c' => 'C — none']],
        ['entity' => 'organisation', 'key' => 'current_pakistan_distributor', 'label' => 'Current Pakistan distributor', 'type' => 'text', 'section' => 'Manufacturer profile', 'visible_for_types' => ['manufacturer_supplier']],
        ['entity' => 'organisation', 'key' => 'fami_qs_status', 'label' => 'FAMI-QS status', 'type' => 'select', 'section' => 'Manufacturer profile', 'visible_for_types' => ['manufacturer_supplier'], 'filterable' => true, 'options' => $certification],
        ['entity' => 'organisation', 'key' => 'gmp_plus_status', 'label' => 'GMP+ status', 'type' => 'select', 'section' => 'Manufacturer profile', 'visible_for_types' => ['manufacturer_supplier'], 'filterable' => true, 'options' => $certification],
        ['entity' => 'organisation', 'key' => 'halal_status', 'label' => 'Halal status', 'type' => 'select', 'section' => 'Manufacturer profile', 'visible_for_types' => ['manufacturer_supplier'], 'filterable' => true, 'options' => $certification],

        // Product: website directory filters and product-page details (Content Blueprint v3 §7.4).
        ['entity' => 'product', 'key' => 'species', 'label' => 'Species', 'type' => 'multiselect', 'section' => 'Website', 'filterable' => true,
            'options' => ['broiler' => 'Broiler', 'layer' => 'Layer', 'breeder' => 'Breeder', 'turkey' => 'Turkey']],
        ['entity' => 'product', 'key' => 'physical_form', 'label' => 'Form', 'type' => 'select', 'section' => 'Website', 'filterable' => true, 'show_in_table' => true,
            'options' => ['powder' => 'Powder', 'granular' => 'Granular', 'liquid' => 'Liquid']],
        ['entity' => 'product', 'key' => 'function', 'label' => 'Function', 'type' => 'multiselect', 'section' => 'Website', 'filterable' => true,
            'options' => ['amino_acid_nutrition' => 'Amino-acid nutrition', 'digestibility' => 'Digestibility', 'gut_health' => 'Gut health', 'mycotoxin_control' => 'Mycotoxin control', 'antioxidant' => 'Antioxidant', 'mineral_nutrition' => 'Mineral nutrition']],
        ['entity' => 'product', 'key' => 'nutritional_function', 'label' => 'Nutritional function', 'type' => 'textarea', 'section' => 'Website',
            'help_text' => 'Nutritional language only: "supports", "contributes to", "is used to supply". No therapeutic claims.'],
        ['entity' => 'product', 'key' => 'country_of_origin', 'label' => 'Country of origin', 'type' => 'text', 'section' => 'Website'],
        ['entity' => 'product', 'key' => 'tds_date', 'label' => 'Date of the TDS the specification comes from', 'type' => 'date', 'section' => 'Website'],
        ['entity' => 'product', 'key' => 'applications', 'label' => 'Typical applications and inclusion guidance', 'type' => 'textarea', 'section' => 'Website'],
        ['entity' => 'product', 'key' => 'applications_reviewed', 'label' => 'Applications reviewed by the nutrition panel', 'type' => 'boolean', 'section' => 'Website',
            'help_text' => 'The website shows the applications only when this is ticked.'],

        // Trial KPIs.
        ['entity' => 'trial', 'key' => 'species', 'label' => 'Species', 'type' => 'select', 'section' => 'Trial KPIs', 'filterable' => true, 'options' => $species],
        ['entity' => 'trial', 'key' => 'bird_count', 'label' => 'Bird count', 'type' => 'number', 'section' => 'Trial KPIs'],
        ['entity' => 'trial', 'key' => 'control_vs_test', 'label' => 'Control vs test design', 'type' => 'textarea', 'section' => 'Trial KPIs'],
        ['entity' => 'trial', 'key' => 'fcr', 'label' => 'FCR', 'type' => 'decimal', 'section' => 'Trial KPIs'],
        ['entity' => 'trial', 'key' => 'body_weight_gain', 'label' => 'Body-weight gain', 'type' => 'decimal', 'section' => 'Trial KPIs'],
        ['entity' => 'trial', 'key' => 'mortality_pct', 'label' => 'Mortality (%)', 'type' => 'percentage', 'section' => 'Trial KPIs'],
        ['entity' => 'trial', 'key' => 'cost_per_kg_live_weight', 'label' => 'Cost per kg live weight', 'type' => 'currency', 'section' => 'Trial KPIs'],
    ],

    'settings' => [
        'currencies' => ['USD', 'PKR', 'SGD', 'EUR', 'CNY'],
        'base_currency' => 'USD',
        'quotation_prefix' => 'AVN-Q',
        'role_labels' => ['specialist' => 'Nutrition'],
    ],
];
