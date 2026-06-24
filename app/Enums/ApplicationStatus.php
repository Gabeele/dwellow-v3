<?php

namespace App\Enums;

enum ApplicationStatus: string
{
    case New = 'new';
    case Reviewing = 'reviewing';
    case Approved = 'approved';
    case Rejected = 'rejected';

    /**
     * Human-readable label for display in the UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Reviewing => 'Reviewing',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
        };
    }
}
