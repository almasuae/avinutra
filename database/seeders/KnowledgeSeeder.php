<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ArticleCategory;
use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\GlossaryTerm;
use Illuminate\Database\Seeder;

/**
 * Knowledge Centre launch content (Content Blueprint v3 §7.9): the glossary,
 * article 1 published as AviNutra Technical Team content, and articles 2–8 as
 * drafts with outlines, to be authored or reviewed by the nutrition panel.
 * Existing records are never overwritten, so edits made in the CRM are kept.
 */
class KnowledgeSeeder extends Seeder
{
    public function run(): void
    {
        foreach (self::glossary() as $term => $definition) {
            GlossaryTerm::query()->firstOrCreate(['term' => $term], ['definition' => $definition, 'is_published' => true]);
        }

        foreach (self::articles() as $article) {
            Article::query()->withTrashed()->firstOrCreate(['slug' => $article['slug']], $article);
        }
    }

    /**
     * @return array<string, string>
     */
    public static function glossary(): array
    {
        return [
            'Amino acid' => 'A building block of protein. Birds need a supply of each essential amino acid from their feed, in the right proportions.',
            'Limiting amino acid' => 'The essential amino acid that is in shortest supply relative to the bird\'s requirement. It limits how well the other amino acids can be used for growth or egg production.',
            'Methionine' => 'An essential, sulphur-containing amino acid. In most practical poultry diets it is the first-limiting amino acid, so it is usually supplemented.',
            'Methionine + cystine (TSAA)' => 'The total sulphur amino acids. Requirements are often given for methionine + cystine, because birds can make cystine from methionine but not the other way round.',
            'DL-Methionine' => 'A methionine source containing equal amounts of the D- and L-forms. Birds convert the D-form into the L-form. DL-Methionine 99% is the usual reference for comparing methionine sources.',
            'L-Methionine' => 'A methionine source containing only the L-form, supplied at 99% and in lower-purity grades such as 90%.',
            'Methionine hydroxy analogue (MHA)' => 'A precursor of methionine (also called HMTBa) that the bird converts into L-methionine. Supplied as a liquid free acid (MHA-FA) or as a calcium salt (MHA-Ca).',
            'Value factor' => 'The kg of DL-Methionine 99% replaced by 1 kg of a product (DL-Methionine 99% = 1.00). Used to compare methionine sources on an equal basis.',
            'Cost per kg of effective methionine' => 'The price per kg of a methionine product divided by its value factor. A better basis for comparison than price per tonne.',
            'Equimolar basis' => 'A comparison of equal molecular amounts of two active substances, regardless of the weight of the products that contain them.',
            'Product basis' => 'A comparison of equal weights of products as sold. For a product that is not 100% active substance, a value on a product basis is lower than on an equimolar basis.',
            'Digestible amino acid' => 'The part of an amino acid in a feed ingredient that the bird can digest and absorb. Modern formulation usually works on digestible rather than total amino acids.',
            'Ideal protein' => 'An amino-acid profile in which each essential amino acid is supplied in proportion to the bird\'s requirement, often expressed relative to lysine.',
            'Crude protein' => 'An estimate of the protein content of a feed or ingredient, calculated from its nitrogen content.',
            'Feed conversion ratio (FCR)' => 'The kg of feed needed to produce 1 kg of live weight (or of eggs). A lower FCR means more efficient feed use.',
            'Least-cost formulation' => 'Formulating a diet that meets set nutrient specifications at the lowest ingredient cost, usually with linear-programming software.',
            'Inclusion rate' => 'The amount of an ingredient in a feed, usually given in kg per tonne or as a percentage.',
            'Premix' => 'A blend of vitamins, trace minerals and sometimes other additives, added to feed at a low inclusion rate.',
            'Feed additive' => 'A product added to feed in small amounts for a nutritional or technical purpose, such as an amino acid, enzyme or preservative.',
            'Phytase' => 'A feed enzyme that releases phosphorus bound in phytate, the main storage form of phosphorus in plant ingredients.',
            'Xylanase' => 'A feed enzyme that breaks down arabinoxylans, fibre components of cereals such as wheat, which can improve nutrient digestibility.',
            'Mycotoxin' => 'A toxic compound produced by certain moulds on crops and stored feed ingredients. Managed through ingredient quality control, storage and, where needed, feed additives.',
            'Assay' => 'The measured content of the active substance in a product, usually given as a percentage.',
            'Loss on drying' => 'The weight lost when a sample is dried under set conditions; a measure of moisture and other volatile matter.',
            'Certificate of analysis (COA)' => 'A document from the manufacturer or a laboratory giving the test results for a specific batch of a product.',
            'Technical data sheet (TDS)' => 'A manufacturer\'s document describing a product\'s typical specification, form, packaging, storage and use.',
            'Safety data sheet (SDS)' => 'A document describing a product\'s hazards and how to handle, store and transport it safely.',
            'FAMI-QS' => 'A certification scheme for the safety and quality of specialty feed ingredients and their mixtures. Certificates can be checked on the scheme\'s public register.',
            'GMP+' => 'A feed-safety certification scheme covering the feed chain. Certificates can be checked on the scheme\'s public register.',
            'FOB (Free On Board)' => 'An Incoterm: the seller delivers the goods on board the ship at the named port of shipment; the buyer pays freight and insurance from there.',
            'CFR (Cost and Freight)' => 'An Incoterm: the seller pays the freight to the named port of destination; the risk passes to the buyer when the goods are on board at the port of shipment.',
            'Landed cost' => 'The total cost of an imported product delivered to the buyer: the purchase price plus freight, insurance, duties, taxes, bank and port charges, and inland delivery.',
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function articles(): array
    {
        return [
            [
                'title' => 'How to Compare Methionine Sources: Cost per kg of Effective Methionine',
                'slug' => 'how-to-compare-methionine-sources',
                'category' => ArticleCategory::FeedEconomics,
                'status' => ArticleStatus::Published,
                'summary' => 'Price per tonne does not tell you what you pay for methionine. This guide shows how to compare DL-Methionine, L-Methionine and MHA on the cost per kg of effective methionine.',
                'body' => self::articleOne(),
                'sources' => [
                    ['title' => 'EFSA FEEDAP Panel (2012). Scientific Opinion on DL-methionine, DL-methionine sodium salt, the hydroxy analogue of methionine and the calcium salt of methionine hydroxy analogue in all animal species. EFSA Journal 10(3):2623', 'url' => 'https://doi.org/10.2903/j.efsa.2012.2623', 'date' => '2012'],
                    ['title' => 'EFSA FEEDAP Panel (2018). Safety and efficacy of hydroxy analogue of methionine and its calcium salt … for all animal species. EFSA Journal 16(3):5198', 'url' => 'https://doi.org/10.2903/j.efsa.2018.5198', 'date' => '2018', 'note' => 'Title shortened: the product (trade) name in the original title is left out, because the site names no trademarks.'],
                ],
                'related_route' => 'tools.methionine-value',
                'last_reviewed_on' => '2026-09-29',
                'published_at' => '2026-09-29 08:00:00',
            ],
            self::draft('DL-Methionine vs L-Methionine: What Feed Manufacturers Should Know', 'dl-methionine-vs-l-methionine', ArticleCategory::PoultryNutrition,
                "- How birds use D- and L-methionine\n- What the evidence shows at equal purity, and where it is uncertain\n- Purity grades (99% and 90%) and how to adjust for them\n- Practical buying checklist"),
            self::draft('MHA vs DL-Methionine: Understanding the Efficacy Debate', 'mha-vs-dl-methionine', ArticleCategory::PoultryNutrition,
                "- What MHA is: a precursor, not methionine\n- Equimolar and product basis explained\n- The range of published positions (regulatory, independent, manufacturers), with sources\n- How to choose and state an assumption in a cost comparison"),
            self::draft('How to Read a Feed-Grade Certificate of Analysis', 'how-to-read-a-certificate-of-analysis', ArticleCategory::IngredientQuality,
                "- What a COA must contain\n- Matching the COA to the batch and the specification\n- Assay, moisture, heavy metals and other key results\n- Warning signs and how to verify"),
            self::draft('How to Evaluate and Qualify a New Feed Ingredient Supplier', 'how-to-qualify-a-feed-ingredient-supplier', ArticleCategory::IngredientQuality,
                "- Identify manufacturer → documentation → technical review → sample → trial → commercial evaluation → supply\n- Verifying certificates on public registers\n- What to ask in due diligence"),
            self::draft('Methionine in Broiler Nutrition: Requirements and Practical Formulation', 'methionine-in-broiler-nutrition', ArticleCategory::PoultryNutrition,
                "- Methionine and methionine + cystine requirements by phase (cite and link published references; no reproduced breeder tables)\n- Digestible basis\n- Practical formulation points"),
            self::draft('Managing Feed Ingredient Supply Risk: Alternative Sourcing Without Surprises', 'managing-feed-ingredient-supply-risk', ArticleCategory::FeedIngredients,
                "- Why supply disruptions happen\n- Qualifying a second source before you need it\n- Substitution: specification, equivalence, inclusion, cost, trial\n- Contract and documentation points"),
            self::draft('Landed Cost: What Really Makes Up the Price of an Imported Feed Additive', 'landed-cost-of-imported-feed-additives', ArticleCategory::FeedEconomics,
                "- From CFR price to landed cost: freight, insurance, duties, sales tax, withholding tax, bank and port charges\n- Gross vs net of recoverable taxes\n- Why rates must be confirmed with a customs broker (not tax advice)"),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected static function draft(string $title, string $slug, ArticleCategory $category, string $outline): array
    {
        return ['title' => $title, 'slug' => $slug, 'category' => $category, 'status' => ArticleStatus::Draft, 'outline' => $outline];
    }

    protected static function articleOne(): string
    {
        return <<<'MD'
Feed mills often compare methionine offers on price per tonne. That comparison is misleading, because the products do not supply the same amount of effective methionine per kg. A cheaper product can cost more per unit of methionine that the bird actually uses.

## The right measure: cost per kg of effective methionine

Compare every source against a common reference. The usual reference is **DL-Methionine 99%**, with a **value factor** of 1.00. The value factor of any other product is the kg of DL-Methionine 99% that 1 kg of that product replaces.

**Cost per kg of effective methionine = price per kg ÷ value factor**

## A worked example

With illustrative prices (not market prices):

| Product | Price (USD/kg) | Value factor | Cost per kg of effective methionine (USD) |
|---|---|---|---|
| DL-Methionine 99% | 2.36 | 1.00 | 2.360 |
| MHA-FA 88% (liquid), about 75% equimolar | 1.76 | 0.65 | 2.708 |
| MHA-FA 88% (liquid), about 80% equimolar | 1.76 | 0.70 | 2.514 |
| MHA-FA 88% (liquid), 100% equimolar | 1.76 | 0.88 | 2.000 |
| L-Methionine 90% | 3.79 | 0.909 | 4.169 |

Two things stand out:

- **Purity matters.** L-Methionine 90% supplies less methionine per kg than a 99% product, so its value factor is lower (0.909 is a purity adjustment only).
- **The assumption for MHA matters most.** At the same price, the MHA-FA result ranges from 2.000 to 2.708 depending on the value factor used. The hydroxy analogue is a precursor of methionine, and its relative efficacy is debated: the European Food Safety Authority concluded that the hydroxy analogues show a somewhat lower bioefficacy than DL-Methionine, while MHA manufacturers state that they are equivalent on an equimolar basis.

## Value factors for MHA-FA 88%

The value factor is on a **product basis**: the kg of DL-Methionine 99% replaced by 1 kg of MHA-FA 88% as sold. Each factor assumes an equimolar efficacy:

- **0.65** — about 75% equimolar efficacy;
- **0.70** — about 80% equimolar, in line with the meta-analysis cited by the European Food Safety Authority in 2018 (79–81%);
- **0.88** — 100% equimolar, the MHA manufacturers' position.

**Value factor ≈ active content (0.88) × equimolar efficacy × (149.2 ÷ 150.2)**, where the last term converts from the hydroxy analogue to methionine by molar mass. These factors are indicative until reviewed by our nutrition panel.

## From cost per kg to cost per tonne of feed

To see the effect on your feed, work out the equivalent inclusion of each product:

- **Equivalent inclusion** = reference inclusion × (reference factor ÷ product factor)
- **Cost per tonne of feed** = equivalent inclusion × price per kg

Multiply the difference per tonne by your monthly feed production to see the monthly and annual effect.

## Good practice

1. Compare products on the same basis — the same price basis (for example CFR or landed) and the same formulation basis (total or digestible).
2. State the value factor you use for each product, and its source.
3. Check the specification and the certificate of analysis: the value factor assumes the product meets its stated purity.
4. Confirm the result with the manufacturer's technical documentation and your nutritionist.

This is an indicative economic comparison. Nutritional equivalence depends on the product, the diet and the formulation basis. For a comparison with your own prices and formulation, ask our nutrition team.
MD;
    }
}
