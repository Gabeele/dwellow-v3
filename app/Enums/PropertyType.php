<?php

namespace App\Enums;

enum PropertyType: string
{
    case House = 'house';
    case Apartment = 'apartment';
    case Condo = 'condo';
    case Townhouse = 'townhouse';
    case Multiplex = 'multiplex';
    case Other = 'other';

    /**
     * Human-readable label for display in the UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::House => 'House',
            self::Apartment => 'Apartment',
            self::Condo => 'Condo',
            self::Townhouse => 'Townhouse',
            self::Multiplex => 'Multiplex',
            self::Other => 'Other',
        };
    }
}
