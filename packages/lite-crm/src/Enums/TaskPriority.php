<?php

declare(strict_types=1);

namespace LiteCrm\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use LiteCrm\Enums\Concerns\HasTranslatedLabel;

enum TaskPriority: string implements HasColor, HasLabel
{
    use HasTranslatedLabel;

    case Low = 'low';
    case Normal = 'normal';
    case High = 'high';
    case Urgent = 'urgent';

    protected static function translationGroup(): string
    {
        return 'task_priority';
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Low => 'gray',
            self::Normal => 'info',
            self::High => 'warning',
            self::Urgent => 'danger',
        };
    }
}
