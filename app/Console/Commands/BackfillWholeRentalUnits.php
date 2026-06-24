<?php

namespace App\Console\Commands;

use App\Enums\RentalType;
use App\Models\Property;
use App\Observers\PropertyObserver;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('properties:backfill-backing-units')]
#[Description('Provision the single backing unit (and default form) for whole-rental properties created before the PropertyObserver existed.')]
class BackfillWholeRentalUnits extends Command
{
    /**
     * Give every whole-rental property that has no unit its single backing unit.
     *
     * Idempotent: only whole rentals missing a unit are touched, and the
     * observer's firstOrCreate guards against a duplicate, so running it twice
     * provisions nothing the second time.
     */
    public function handle(PropertyObserver $observer): int
    {
        $properties = Property::query()
            ->where('rental_type', RentalType::Whole)
            ->doesntHave('units')
            ->get();

        foreach ($properties as $property) {
            $observer->provisionBackingUnit($property);
        }

        $this->info("Backfilled {$properties->count()} whole-rental "
            .str('property')->plural($properties->count()).'.');

        return self::SUCCESS;
    }
}
