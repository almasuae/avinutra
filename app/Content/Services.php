<?php

declare(strict_types=1);

namespace App\Content;

/**
 * Nutrition Services pages (Content Blueprint v3 §7.3). Company content,
 * written cautiously: what we do and how, never claims of past work.
 */
class Services
{
    /**
     * @return array<string, array{title: string, icon: string, summary: string, lead: string, sections: list<array{heading: string, text?: string, items?: list<string>}>, flow?: list<string>, flow_label?: string}>
     */
    public static function all(): array
    {
        return [
            'formulation-support' => [
                'title' => 'Formulation Support',
                'icon' => 'heroicon-o-beaker',
                'summary' => 'Broiler, layer and breeder formulation, least-cost review and amino-acid balancing.',
                'lead' => 'Practical formulation support for poultry feed manufacturers: we review your diets with you and help you meet nutrient targets at a sensible cost.',
                'sections' => [
                    ['heading' => 'What we can help with', 'items' => [
                        'Broiler, layer and breeder formulation.',
                        'Starter, grower and finisher programmes.',
                        'Least-cost formulation review: constraints, nutrient matrices and ingredient limits.',
                        'Nutrient-density assessment against your performance and cost targets.',
                        'Amino-acid balancing on a digestible basis, including methionine, lysine, threonine and valine.',
                    ]],
                    ['heading' => 'How we work', 'text' => 'We start from your current formulations, ingredient prices and performance data. We explain the reasoning behind every recommendation, so your own nutritionist stays in control of the final formulation.'],
                ],
            ],
            'ingredient-evaluation' => [
                'title' => 'Ingredient Evaluation',
                'icon' => 'heroicon-o-clipboard-document-check',
                'summary' => 'Specification, nutritional value, quality, consistency, supplier reliability and landed cost.',
                'lead' => 'Before an ingredient enters your formulation, it should be judged on more than its price. We evaluate it on the factors that decide its real value to your feed mill.',
                'sections' => [
                    ['heading' => 'What we evaluate', 'items' => [
                        'Specification: assay, moisture, contaminants and physical form.',
                        'Nutritional value in your formulation, not only on paper.',
                        'Quality and batch-to-batch consistency, from certificates of analysis.',
                        'Supplier reliability and documentation.',
                        'Landed cost in your market.',
                        'The impact on your formulation and cost per tonne of feed.',
                    ]],
                ],
            ],
            'product-substitution' => [
                'title' => 'Product Substitution',
                'icon' => 'heroicon-o-arrows-right-left',
                'summary' => '"Can Product A replace Product B?" — assessed step by step.',
                'lead' => '"Can Product A replace Product B?" is one of the most common questions a feed mill faces, especially after a supply disruption. We answer it step by step.',
                'flow' => ['Specification', 'Nutritional equivalence', 'Inclusion', 'Cost', 'Trial', 'Performance'],
                'flow_label' => 'How we assess a substitution',
                'sections' => [
                    ['heading' => 'Why a step-by-step approach', 'text' => 'Two products with similar names can differ in purity, form and nutritional value. We compare them on an equal basis, work out the inclusion that gives the same nutritional contribution, calculate the cost difference, and recommend a trial before a full switch where the change is significant.'],
                ],
            ],
            'feed-economics' => [
                'title' => 'Feed Economics',
                'icon' => 'heroicon-o-calculator',
                'summary' => 'Cost per kg of active nutrient, per tonne of feed, per bird and per kg of live weight.',
                'lead' => 'The cheapest ingredient per tonne is not always the cheapest in the feed. We compare options on the costs that matter to your business.',
                'sections' => [
                    ['heading' => 'The costs we calculate', 'items' => [
                        'Cost per kg of active nutrient.',
                        'Cost per kg of effective methionine.',
                        'Cost per tonne of feed.',
                        'Feed cost per bird.',
                        'Feed cost per kg of live weight.',
                    ]],
                    ['heading' => 'Why it matters', 'text' => 'Comparing products on these measures shows the real economic difference between them, and helps you decide where a change in specification or supplier is worth making.'],
                ],
            ],
            'supplier-qualification' => [
                'title' => 'Supplier Qualification',
                'icon' => 'heroicon-o-shield-check',
                'summary' => 'From identifying a manufacturer to reliable supply, one documented step at a time.',
                'lead' => 'A new supplier should earn its place in your supply chain. Our supplier qualification process follows a clear sequence, with documentation at each step.',
                'flow' => ['Identify manufacturer', 'Documentation', 'Technical review', 'Sample', 'Trial', 'Commercial evaluation', 'Supply'],
                'flow_label' => 'Our supplier qualification process',
                'sections' => [
                    ['heading' => 'What we check', 'text' => 'Our supplier qualification process includes a review of the manufacturer\'s documents — specifications, certificates of analysis, safety data sheets and quality certificates — and verification of certificates against the issuing body\'s public register where one exists.'],
                ],
            ],
            'technical-trials' => [
                'title' => 'Technical Trials',
                'icon' => 'heroicon-o-chart-bar',
                'summary' => 'Structured trials with control and test groups, and an economic interpretation.',
                'lead' => 'A well-designed trial shows whether a product change delivers in your conditions. We help you plan, run and interpret it.',
                'sections' => [
                    ['heading' => 'A sound trial protocol', 'items' => [
                        'A baseline formulation.',
                        'Control and test groups.',
                        'A defined inclusion and duration.',
                        'Agreed performance indicators.',
                        'Statistical and technical review of the results.',
                        'An economic interpretation of the outcome.',
                    ]],
                    ['heading' => 'Your results stay yours', 'text' => 'Trial results are published only with the customer\'s written consent.'],
                ],
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function slugs(): array
    {
        return array_keys(self::all());
    }
}
