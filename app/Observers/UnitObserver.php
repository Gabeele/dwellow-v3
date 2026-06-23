<?php

namespace App\Observers;

use App\Models\Unit;
use App\Screening\DefaultApplicationForm;

class UnitObserver
{
    /**
     * Provision the dwellow default application form for a newly created unit.
     *
     * Uses firstOrCreate so the form is the observer's single source of truth:
     * a unit that somehow already has a form is left untouched (no duplicates).
     */
    public function created(Unit $unit): void
    {
        $unit->applicationForm()->firstOrCreate([], [
            'fields' => DefaultApplicationForm::fields(),
        ]);
    }
}
