<?php

declare(strict_types=1);

namespace LiteCrm\Enums;

use Filament\Support\Contracts\HasLabel;
use LiteCrm\Enums\Concerns\HasTranslatedLabel;

enum PriceSourceType: string implements HasLabel
{
    use HasTranslatedLabel;

    case SupplierOffer = 'supplier_offer';
    case CustomsDerived = 'customs_derived';
    case PublishedAssessment = 'published_assessment';
    case MarketReport = 'market_report';

    protected static function translationGroup(): string
    {
        return 'price_source_type';
    }
}
