<?php

declare(strict_types=1);

namespace LiteCrm\Enums;

use Filament\Support\Contracts\HasLabel;
use LiteCrm\Enums\Concerns\HasTranslatedLabel;

/**
 * The legal basis for holding a contact's personal data.
 */
enum ConsentBasis: string implements HasLabel
{
    use HasTranslatedLabel;

    case Consent = 'consent';
    case Contract = 'contract';
    case LegitimateInterest = 'legitimate_interest';
    case Other = 'other';

    protected static function translationGroup(): string
    {
        return 'consent_basis';
    }
}
