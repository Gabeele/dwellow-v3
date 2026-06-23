<?php

namespace App\Enums;

enum Role: string
{
    case Landlord = 'landlord';
    case Tenant = 'tenant';

    /**
     * Human-readable label for display in the UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::Landlord => 'Landlord',
            self::Tenant => 'Tenant',
        };
    }
}
