<?php

declare(strict_types=1);

namespace LiteCrm\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use LiteCrm\Enums\Concerns\HasTranslatedLabel;

enum TrialStatus: string implements HasColor, HasLabel
{
    use HasTranslatedLabel;

    case Planned = 'planned';
    case Running = 'running';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    protected static function translationGroup(): string
    {
        return 'trial_status';
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Planned => 'gray',
            self::Running => 'info',
            self::Completed => 'success',
            self::Cancelled => 'danger',
        };
    }
}
