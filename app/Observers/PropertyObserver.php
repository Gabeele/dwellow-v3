<?php

namespace App\Observers;

use App\Models\Property;
use App\Screening\BackingUnitProvisioner;

class PropertyObserver
{
    /**
     * Provision a single backing unit for a newly created whole-rental property.
     *
     * Whole rentals screen against exactly one backing unit. The actual
     * provisioning lives in {@see BackingUnitProvisioner} so the create event,
     * the controller's show heal, and any backfill all share one source of
     * truth. Multi-unit properties are untouched — they create their own units.
     */
    public function created(Property $property): void
    {
        $this->provisionBackingUnit($property);
    }

    /**
     * Ensure a whole-rental property has its single backing unit.
     *
     * Idempotent and safe to call outside the created event (e.g. the backfill
     * command for legacy whole rentals). Delegates to {@see BackingUnitProvisioner}
     * and is a no-op for multi-unit properties.
     */
    public function provisionBackingUnit(Property $property): void
    {
        if (BackingUnitProvisioner::applies($property)) {
            BackingUnitProvisioner::ensure($property);
        }
    }
}
