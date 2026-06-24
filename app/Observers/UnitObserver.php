<?php

namespace App\Observers;

use App\Models\Unit;

class UnitObserver
{
    /**
     * Provision the screening surface for a newly created unit: its default
     * application form and its single shareable application link.
     *
     * Both helpers are idempotent, so a unit that somehow already has a form or
     * link is left untouched (no duplicates).
     */
    public function created(Unit $unit): void
    {
        $unit->applicationFormOrDefault();
        $unit->applicationLinkOrDefault();
    }
}
