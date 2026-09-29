<?php

declare(strict_types=1);

namespace App\Support;

/**
 * The website's enquiry forms (Content Blueprint v3 §8). Each maps to a CRM
 * enquiry type, which routes it to the matching mailbox (AviNutraSeeder).
 * Rendered with the Lite CRM component: <livewire:lite-crm.enquiry-form>.
 */
class EnquiryForms
{
    public const SPECIES = ['broiler' => 'Broiler', 'layer' => 'Layer', 'breeder' => 'Breeder', 'turkey' => 'Turkey', 'other' => 'Other'];

    public const TOPICS = [
        'formulation-support' => 'Formulation support',
        'ingredient-evaluation' => 'Ingredient evaluation',
        'product-substitution' => 'Product substitution',
        'feed-economics' => 'Feed economics',
        'supplier-qualification' => 'Supplier qualification',
        'technical-trials' => 'Technical trials',
        'methionine' => 'Methionine and amino acids',
        'other' => 'Something else',
    ];

    /**
     * The Contact page's enquiry-type picker (v3 §7.10), in display order.
     *
     * @return array<string, array{label: string, hint: string}>
     */
    public static function contactTypes(bool $withCall = false): array
    {
        $types = [
            'general' => ['label' => 'General', 'hint' => 'Anything else, or if you are not sure.'],
            'quotation' => ['label' => 'Sales / Quotation', 'hint' => 'Price and availability for a product.'],
            'ask_nutritionist' => ['label' => 'Technical / Ask a Nutritionist', 'hint' => 'Formulation, ingredients or feed economics.'],
            'supplier_application' => ['label' => 'Supplier partnership', 'hint' => 'For manufacturers of feed ingredients.'],
            'sourcing_request' => ['label' => 'Sourcing request', 'hint' => 'A product you need that we do not list.'],
            'sample' => ['label' => 'Sample request', 'hint' => 'A sample for evaluation or a trial.'],
            'document' => ['label' => 'Document request', 'hint' => 'TDS, SDS, COA, certificates or other documents.'],
        ];

        if ($withCall) {
            $types['call'] = ['label' => 'Request a call', 'hint' => 'We will call you back.'];
        }

        return $types;
    }

    /**
     * The component settings for a form.
     *
     * @param  array<string, string>  $values  pre-filled answers
     * @return array{type: string, fields: array<int|string, mixed>, uploads: int, submit: string, values: array<string, string>}
     */
    public static function form(string $key, array $values = []): array
    {
        $contact = [
            'name' => ['required' => true],
            'company' => ['required' => true],
            'email' => ['required' => true],
            'phone' => ['label' => 'Phone or WhatsApp'],
            'country' => ['type' => 'country', 'required' => true],
        ];

        $definition = match ($key) {
            'ask_nutritionist' => [
                'type' => 'ask_nutritionist',
                'fields' => [
                    'name' => ['required' => true],
                    'company' => ['required' => true],
                    'role' => ['label' => 'Your role'],
                    'email' => ['required' => true],
                    'phone' => ['label' => 'WhatsApp number'],
                    'country' => ['type' => 'country', 'required' => true],
                    'city' => [],
                    'topic' => ['label' => 'Topic', 'type' => 'select', 'options' => self::TOPICS],
                    'species' => ['label' => 'Species', 'type' => 'select', 'options' => self::SPECIES],
                    'feed_type' => ['label' => 'Feed type (e.g. broiler starter)'],
                    'current_product' => ['label' => 'Product you use now'],
                    'message' => ['label' => 'Your question', 'required' => true],
                ],
                'uploads' => 2,
                'submit' => 'Send my question',
            ],
            'sourcing_request' => [
                'type' => 'sourcing_request',
                'fields' => [
                    ...$contact,
                    'product' => ['label' => 'Product', 'required' => true],
                    'specification' => ['label' => 'Specification', 'type' => 'textarea'],
                    'quantity_per_month' => ['label' => 'Quantity per month'],
                    'annual_requirement' => ['label' => 'Annual requirement'],
                    'current_supplier' => ['label' => 'Current supplier (optional)'],
                    'current_price_range' => ['label' => 'Current price range (optional)'],
                    'delivery_location' => ['label' => 'Delivery location', 'required' => true],
                    'documents_required' => ['label' => 'Documents required'],
                    'target_delivery_date' => ['label' => 'Target delivery date'],
                    'message' => ['label' => 'Anything else we should know', 'required' => false],
                ],
                'uploads' => 1,
                'submit' => 'Request sourcing support',
            ],
            'quotation', 'sample', 'document' => [
                'type' => $key,
                'fields' => [
                    ...$contact,
                    'product' => ['label' => 'Product', 'required' => true],
                    'quantity' => ['label' => $key === 'sample' ? 'Sample quantity' : 'Quantity'],
                    'delivery_point' => ['label' => 'Delivery point'],
                    'documents_needed' => ['label' => 'Documents needed', 'required' => $key === 'document'],
                    'message' => ['label' => 'Message'],
                ],
                'uploads' => 0,
                'submit' => match ($key) {
                    'quotation' => 'Request quotation',
                    'sample' => 'Request sample',
                    default => 'Request documents',
                },
            ],
            'supplier_application' => [
                'type' => 'supplier_application',
                'fields' => [
                    'company' => ['label' => 'Company', 'required' => true],
                    'country' => ['type' => 'country', 'required' => true],
                    'website' => ['label' => 'Website'],
                    'name' => ['label' => 'Contact person', 'required' => true],
                    'email' => ['required' => true],
                    'phone' => [],
                    'product_categories' => ['label' => 'Product categories', 'type' => 'textarea', 'required' => true],
                    'manufacturing_sites' => ['label' => 'Manufacturing sites'],
                    'annual_capacity' => ['label' => 'Annual capacity'],
                    'existing_business' => ['label' => 'Existing business in your target markets, and current distributors', 'type' => 'textarea'],
                    'export_markets' => ['label' => 'Export markets'],
                    'certifications' => ['label' => 'Certifications (e.g. FAMI-QS, GMP+, ISO, halal)'],
                    'desired_territory' => ['label' => 'Desired territory'],
                    'exclusivity' => ['label' => 'Exclusivity expectations'],
                    'moq' => ['label' => 'Minimum order quantity'],
                    'lead_time' => ['label' => 'Lead time'],
                    'payment_terms' => ['label' => 'Payment terms'],
                    'technical_support' => ['label' => 'Technical support you can provide'],
                    'sample_availability' => ['label' => 'Sample availability'],
                    'message' => ['label' => 'Anything else', 'required' => false],
                ],
                'uploads' => 5,
                'submit' => 'Send application',
            ],
            'call' => [
                'type' => 'general',
                'fields' => [
                    'name' => ['required' => true],
                    'company' => [],
                    'phone' => ['label' => 'Phone or WhatsApp', 'required' => true],
                    'email' => ['required' => true],
                    'country' => ['type' => 'country', 'required' => true],
                    'message' => ['label' => 'Best time to call, and the topic', 'required' => true],
                ],
                'uploads' => 0,
                'submit' => 'Request a call',
            ],
            default => [
                'type' => 'general',
                'fields' => [
                    'name' => ['required' => true],
                    'company' => [],
                    'country' => ['type' => 'country', 'required' => true],
                    'email' => ['required' => true],
                    'message' => ['required' => true],
                ],
                'uploads' => 0,
                'submit' => 'Send message',
            ],
        };

        return [...$definition, 'values' => $values];
    }
}
