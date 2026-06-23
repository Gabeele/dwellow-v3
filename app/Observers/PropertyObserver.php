<?php

namespace App\Observers;

use App\Enums\RentalType;
use App\Models\Property;

class PropertyObserver
{
    /**
     * Provision a single backing unit for a newly created whole-rental property.
     *
     * The data model treats a whole rental as a property with exactly one unit
     * ("screening happens at the unit level"), so every whole property needs a
     * backing unit to carry its application form, links, and applicants. The
     * unit's own UnitObserver then auto-provisions the default application form.
     *
     * Uses firstOrCreate so a re-fire (or a property that somehow already has a
     * unit) never produces a second backing unit. Multi-unit properties are
     * untouched — they create their own units via the UnitController.
     */
    public function created(Property $property): void
    {
        $this->provisionBackingUnit($property);
    }

    /**
     * Ensure a whole-rental property has its single backing unit.
     *
     * Idempotent and safe to call outside the created event (e.g. a backfill
     * for legacy whole rentals): firstOrCreate guarantees at most one unit and
     * the UnitObserver provisions its default form. A no-op for multi-unit
     * properties, which carry their own units.
     */
    public function provisionBackingUnit(Property $property): void
    {
        if ($property->rental_type !== RentalType::Whole) {
            return;
        }

        $property->units()->firstOrCreate([], [
            'label' => $property->name ?? __('Whole property'),
            'bedrooms' => $property->bedrooms,
            'bathrooms' => $property->bathrooms,
            'rent_amount' => $property->rent_amount,
            'status' => $property->status,
        ]);
    }
}
