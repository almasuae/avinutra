<?php

declare(strict_types=1);

namespace LiteCrm\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use LiteCrm\Enums\Concerns\HasTranslatedLabel;

enum ProductAvailability: string implements HasColor, HasLabel
{
    use HasTranslatedLabel;

    case Available = 'available';
    case OnRequest = 'on_request';
    case Information = 'information';

    protected static function translationGroup(): string
    {
        return 'product_availability';
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Available => 'success',
            self::OnRequest => 'info',
            self::Information => 'gray',
        };
    }
}
