<?php

namespace App\Policies;

use App\Models\Unit;
use App\Models\User;

class UnitPolicy
{
    /**
     * Determine whether the user can view the unit.
     */
    public function view(User $user, Unit $unit): bool
    {
        return $this->owns($user, $unit);
    }

    /**
     * Determine whether the user can update the unit.
     */
    public function update(User $user, Unit $unit): bool
    {
        return $this->owns($user, $unit);
    }

    /**
     * Determine whether the user can delete the unit.
     */
    public function delete(User $user, Unit $unit): bool
    {
        return $this->owns($user, $unit);
    }

    /**
     * A landlord may only act on units of properties they own.
     */
    private function owns(User $user, Unit $unit): bool
    {
        return $user->isLandlord() && $unit->property->landlord_id === $user->id;
    }
}
