<?php

namespace App\Observers;

use App\Models\Unit;

class UnitObserver
{
    /**
     * Provision the dwellow default application form for a newly created unit.
     *
     * Delegates to Unit::applicationFormOrDefault(), whose firstOrCreate leaves
     * a unit that somehow already has a form untouched (no duplicates).
     */
    public function created(Unit $unit): void
    {
        $unit->applicationFormOrDefault();
    }
}
