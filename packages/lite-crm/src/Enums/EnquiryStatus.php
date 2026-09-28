<?php

declare(strict_types=1);

namespace LiteCrm\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use LiteCrm\Enums\Concerns\HasTranslatedLabel;

enum EnquiryStatus: string implements HasColor, HasLabel
{
    use HasTranslatedLabel;

    case New = 'new';
    case Assigned = 'assigned';
    case InProgress = 'in_progress';
    case Converted = 'converted';
    case Closed = 'closed';
    case Spam = 'spam';

    protected static function translationGroup(): string
    {
        return 'enquiry_status';
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::New, self::Assigned, self::InProgress], true);
    }

    /**
     * Whether reaching this status counts as a first response to the sender.
     */
    public function isResponse(): bool
    {
        return in_array($this, [self::InProgress, self::Converted, self::Closed], true);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::New => 'warning',
            self::Assigned => 'info',
            self::InProgress => 'primary',
            self::Converted => 'success',
            self::Closed => 'gray',
            self::Spam => 'danger',
        };
    }
}
