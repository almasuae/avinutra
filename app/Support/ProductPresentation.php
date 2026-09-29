<?php

declare(strict_types=1);

namespace App\Support;

use LiteCrm\Enums\ProductAvailability;
use LiteCrm\Models\Product;

/**
 * How a CRM product is presented on the website (Content Blueprint v3 §7.4):
 * the availability wording and the calls to action that go with it.
 */
class ProductPresentation
{
    public static function badge(ProductAvailability $availability): string
    {
        return match ($availability) {
            ProductAvailability::Available => 'Available',
            ProductAvailability::OnRequest => 'Sourced on request',
            ProductAvailability::Information => 'Technical information',
        };
    }

    public static function statement(ProductAvailability $availability): ?string
    {
        return match ($availability) {
            // No company or country details on the public site (decision of 29 Sep 2026).
            ProductAvailability::Available => 'Available',
            ProductAvailability::OnRequest => 'Sourced on request',
            ProductAvailability::Information => null,
        };
    }

    /**
     * The request buttons for a product page: [label, contact form type, extra values].
     *
     * @return list<array{0: string, 1: string, 2: array<string, string>}>
     */
    public static function requests(Product $product): array
    {
        return match ($product->availability) {
            ProductAvailability::Available => [
                ['Request Quotation', 'quotation', []],
                ['Request Sample', 'sample', []],
                ['Request TDS', 'document', ['documents_needed' => 'TDS']],
                ['Request COA', 'document', ['documents_needed' => 'COA']],
            ],
            ProductAvailability::OnRequest => [
                ['Request Sourcing Support', 'sourcing_request', []],
                ['Request TDS', 'document', ['documents_needed' => 'TDS']],
            ],
            ProductAvailability::Information => [],
        };
    }
}
