<?php

declare(strict_types=1);

namespace LiteCrm\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use LiteCrm\Enums\Concerns\HasTranslatedLabel;

enum TaskStatus: string implements HasColor, HasLabel
{
    use HasTranslatedLabel;

    case Open = 'open';
    case InProgress = 'in_progress';
    case Done = 'done';
    case Cancelled = 'cancelled';

    protected static function translationGroup(): string
    {
        return 'task_status';
    }

    public function isClosed(): bool
    {
        return $this === self::Done || $this === self::Cancelled;
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Open => 'gray',
            self::InProgress => 'info',
            self::Done => 'success',
            self::Cancelled => 'danger',
        };
    }
}
