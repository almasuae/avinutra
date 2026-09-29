<?php

declare(strict_types=1);

namespace LiteCrm\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use LiteCrm\Enums\Concerns\HasTranslatedLabel;

enum PriceConfidence: string implements HasColor, HasLabel
{
    use HasTranslatedLabel;

    case Verified = 'verified';
    case Reported = 'reported';
    case Unconfirmed = 'unconfirmed';

    protected static function translationGroup(): string
    {
        return 'price_confidence';
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Verified => 'success',
            self::Reported => 'info',
            self::Unconfirmed => 'warning',
        };
    }
}
