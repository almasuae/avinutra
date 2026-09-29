<?php

declare(strict_types=1);

namespace LiteCrm\Enums;

use Filament\Support\Contracts\HasLabel;
use LiteCrm\Enums\Concerns\HasTranslatedLabel;

enum PriceBasis: string implements HasLabel
{
    use HasTranslatedLabel;

    case Exw = 'EXW';
    case Fob = 'FOB';
    case Cfr = 'CFR';
    case Cif = 'CIF';
    case Landed = 'landed';

    protected static function translationGroup(): string
    {
        return 'price_basis';
    }
}
