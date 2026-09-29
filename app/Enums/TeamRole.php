<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * How a person works with AviNutra. Advisers are never presented as employees (v3 §7.2).
 */
enum TeamRole: string implements HasLabel
{
    case Adviser = 'adviser';
    case Employee = 'employee';
    case Consultant = 'consultant';

    public function getLabel(): string
    {
        return match ($this) {
            self::Adviser => 'Adviser',
            self::Employee => 'Employee',
            self::Consultant => 'Consultant',
        };
    }
}
