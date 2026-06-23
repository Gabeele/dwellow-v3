<?php

namespace App\Enums;

enum Role: string
{
    case Landlord = 'landlord';
    case Tenant = 'tenant';
    case Admin = 'admin';

    /**
     * Human-readable label for display in the UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::Landlord => 'Landlord',
            self::Tenant => 'Tenant',
            self::Admin => 'Admin',
        };
    }
}
