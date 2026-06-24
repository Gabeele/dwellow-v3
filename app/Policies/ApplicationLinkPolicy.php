<?php

namespace App\Policies;

use App\Models\ApplicationLink;
use App\Models\Unit;
use App\Models\User;

class ApplicationLinkPolicy
{
    /**
     * Determine whether the user can view the application link.
     */
    public function view(User $user, ApplicationLink $applicationLink): bool
    {
        return $this->owns($user, $applicationLink);
    }

    /**
     * Determine whether the user can create an application link for the given unit.
     */
    public function create(User $user, Unit $unit): bool
    {
        return $user->isLandlord() && $unit->property->landlord_id === $user->id;
    }

    /**
     * Determine whether the user can update the application link.
     */
    public function update(User $user, ApplicationLink $applicationLink): bool
    {
        return $this->owns($user, $applicationLink);
    }

    /**
     * Determine whether the user can delete (revoke) the application link.
     */
    public function delete(User $user, ApplicationLink $applicationLink): bool
    {
        return $this->owns($user, $applicationLink);
    }

    /**
     * A landlord may only act on links for units of properties they own.
     */
    private function owns(User $user, ApplicationLink $applicationLink): bool
    {
        return $user->isLandlord() && $applicationLink->unit->property->landlord_id === $user->id;
    }
}
