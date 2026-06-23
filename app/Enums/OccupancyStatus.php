<?php

namespace App\Enums;

enum OccupancyStatus: string
{
    case Available = 'available';
    case Occupied = 'occupied';
    case Unavailable = 'unavailable';

    /**
     * Human-readable label for display in the UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::Available => 'Available',
            self::Occupied => 'Occupied',
            self::Unavailable => 'Unavailable',
        };
    }
}
