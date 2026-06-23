<?php

namespace App\Screening;

use App\Enums\RentalType;
use App\Models\Property;
use App\Models\Unit;

class BackingUnitProvisioner
{
    /**
     * Ensure a whole-rental property has its single backing unit.
     *
     * The data model treats a whole rental as a property with exactly one unit
     * ("screening happens at the unit level"), so every whole property needs a
     * backing unit to carry its application form, links, and applicants. The
     * UnitObserver then auto-provisions that unit's default application form.
     *
     * Idempotent: firstOrCreate guarantees at most one backing unit, so it is
     * safe to call on create, on show (healing legacy whole rentals), or in a
     * backfill. A no-op for multi-unit properties, which carry their own units.
     */
    public static function ensure(Property $property): Unit
    {
        return $property->units()->firstOrCreate([], [
            'label' => $property->name ?? __('Whole property'),
            'bedrooms' => $property->bedrooms,
            'bathrooms' => $property->bathrooms,
            'rent_amount' => $property->rent_amount,
            'status' => $property->status,
        ]);
    }

    /**
     * Whether the given property is one that requires a backing unit.
     */
    public static function applies(Property $property): bool
    {
        return $property->rental_type === RentalType::Whole;
    }
}
