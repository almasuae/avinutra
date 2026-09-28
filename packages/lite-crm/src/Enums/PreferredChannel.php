<?php

declare(strict_types=1);

namespace LiteCrm\Enums;

use Filament\Support\Contracts\HasLabel;
use LiteCrm\Enums\Concerns\HasTranslatedLabel;

enum PreferredChannel: string implements HasLabel
{
    use HasTranslatedLabel;

    case Email = 'email';
    case Phone = 'phone';
    case WhatsApp = 'whatsapp';
    case InPerson = 'in_person';

    protected static function translationGroup(): string
    {
        return 'preferred_channel';
    }
}
