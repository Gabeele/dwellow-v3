<?php

namespace App\Enums;

enum RentalType: string
{
    case Whole = 'whole';
    case MultiUnit = 'multi_unit';

    /**
     * Human-readable label for display in the UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::Whole => 'Rented as a whole',
            self::MultiUnit => 'Split into units',
        };
    }
}
