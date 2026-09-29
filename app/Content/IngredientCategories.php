<?php

declare(strict_types=1);

namespace App\Content;

/**
 * Descriptions for the ingredient category pages (Content Blueprint v3 §7.4).
 * Keys are the CRM list keys (product_category); URLs use them with hyphens.
 * The products themselves come from CRM › Products ("publish on website").
 */
class IngredientCategories
{
    /**
     * @return array<string, array{title: string, icon: string, summary: string, text: string, examples: list<string>}>
     */
    public static function all(): array
    {
        return [
            'amino_acids' => [
                'title' => 'Amino Acids',
                'icon' => 'heroicon-o-beaker',
                'summary' => 'Methionine, lysine, threonine and valine for balanced, cost-effective protein nutrition.',
                'text' => 'Supplementary amino acids allow feed manufacturers to meet digestible amino-acid requirements while controlling crude protein and feed cost. They are among the most economically important inputs in modern poultry feed, so their specification and value per kg matter.',
                'examples' => ['DL-Methionine', 'L-Methionine', 'Methionine hydroxy analogue (MHA)', 'Lysine', 'Threonine', 'Valine'],
            ],
            'enzymes' => [
                'title' => 'Enzymes',
                'icon' => 'heroicon-o-sparkles',
                'summary' => 'Phytase, xylanase, protease and multi-enzyme products that support nutrient digestibility.',
                'text' => 'Feed enzymes are used to improve the digestibility of nutrients in feed ingredients, which can allow a more economical formulation. Their value depends on the product, the diet and the matrix values applied.',
                'examples' => ['Phytase', 'Xylanase', 'Protease', 'Multi-enzyme products'],
            ],
            'vitamins_minerals' => [
                'title' => 'Vitamins & Minerals',
                'icon' => 'heroicon-o-cube-transparent',
                'summary' => 'Vitamin and trace-mineral sources for complete poultry diets.',
                'text' => 'Vitamins and trace minerals are supplied through premixes or individual sources. Specification, stability and the chemical form of the source all affect their value in feed.',
                'examples' => ['Vitamin sources', 'Trace-mineral sources'],
            ],
            'mycotoxin_management' => [
                'title' => 'Mycotoxin Management',
                'icon' => 'heroicon-o-funnel',
                'summary' => 'Products used as part of a mycotoxin-control programme.',
                'text' => 'Mycotoxin management starts with ingredient quality control and storage. Feed additives for mycotoxin control are one part of that programme; their use should be matched to the mycotoxins present and the product\'s documented mode of action.',
                'examples' => ['Mycotoxin-control additives'],
            ],
            'gut_health' => [
                'title' => 'Gut Health',
                'icon' => 'heroicon-o-heart',
                'summary' => 'Feed additives that support digestive function and nutrient use.',
                'text' => 'Gut-health additives are used to support digestive function and the efficient use of nutrients. We discuss them in nutritional terms and on the basis of the manufacturer\'s documentation.',
                'examples' => ['Gut-health additives'],
            ],
            'specialty_additives' => [
                'title' => 'Specialty Additives',
                'icon' => 'heroicon-o-squares-plus',
                'summary' => 'Choline, betaine, organic acids and antioxidants.',
                'text' => 'Specialty additives cover a range of nutritional and technical functions in feed, from methyl-group donors to feed preservation. Each is evaluated on its specification and its cost of use.',
                'examples' => ['Choline', 'Betaine', 'Organic acids', 'Antioxidants'],
            ],
        ];
    }

    public static function slug(string $key): string
    {
        return str_replace('_', '-', $key);
    }

    public static function key(string $slug): string
    {
        return str_replace('-', '_', $slug);
    }
}
