<?php

declare(strict_types=1);

/*
 * Public-site structure (Design Brief §5 and §6, Content Blueprint v3 site map).
 * Items point at route names. A link is shown only when its route exists, so
 * pages that are not built yet are never linked (v3: no dead links).
 */
return [

    'navigation' => [
        ['label' => 'About', 'route' => 'about'],
        ['label' => 'Nutrition Services', 'route' => 'services'],
        ['label' => 'Ingredients', 'route' => 'ingredients'],
        ['label' => 'For Suppliers', 'route' => 'suppliers'],
        ['label' => 'Insights', 'route' => 'knowledge'],
        ['label' => 'Tools', 'route' => 'tools'],
        ['label' => 'Quality', 'route' => 'quality'],
        ['label' => 'Contact', 'route' => 'contact'],
    ],

    // Header button (Design Brief §5: "Get in Touch" by default, or "Ask a Nutritionist").
    'cta' => ['label' => 'Get in Touch', 'route' => 'contact'],

    // Site search is not built yet; the header shows the search icon only when this is true.
    'search' => false,

    'footer_columns' => [
        'Services' => [
            ['label' => 'Nutrition Services', 'route' => 'services'],
            ['label' => 'Solutions for Feed Manufacturers', 'route' => 'services.feed-mills'],
            ['label' => 'Ingredients', 'route' => 'ingredients'],
            ['label' => 'Request Sourcing', 'route' => 'services.request-sourcing'],
            ['label' => 'Ask a Nutritionist', 'route' => 'ask-a-nutritionist'],
        ],
        'Resources' => [
            ['label' => 'Tools', 'route' => 'tools'],
            ['label' => 'Insights', 'route' => 'knowledge'],
            ['label' => 'Quality', 'route' => 'quality'],
            ['label' => 'For Suppliers', 'route' => 'suppliers'],
        ],
    ],

    'legal' => [
        ['label' => 'Privacy', 'route' => 'legal.privacy'],
        ['label' => 'Terms of Use', 'route' => 'legal.terms'],
        ['label' => 'Cookies', 'route' => 'legal.cookies'],
        ['label' => 'Technical Disclaimer', 'route' => 'legal.technical-disclaimer'],
    ],

];
