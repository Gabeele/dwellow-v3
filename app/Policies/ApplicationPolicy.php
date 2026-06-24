<?php

namespace App\Policies;

use App\Models\Application;
use App\Models\User;

class ApplicationPolicy
{
    /**
     * Determine whether the user can view their portfolio-wide applications list.
     */
    public function viewAny(User $user): bool
    {
        return $user->isLandlord();
    }

    /**
     * Determine whether the user can view the application.
     */
    public function view(User $user, Application $application): bool
    {
        return $this->owns($user, $application);
    }

    /**
     * Determine whether the user can update the application.
     */
    public function update(User $user, Application $application): bool
    {
        return $this->owns($user, $application);
    }

    /**
     * Determine whether the user can delete the application.
     */
    public function delete(User $user, Application $application): bool
    {
        return $this->owns($user, $application);
    }

    /**
     * A landlord may only act on applications for units of properties they own.
     */
    private function owns(User $user, Application $application): bool
    {
        return $user->isLandlord() && $application->unit->property->landlord_id === $user->id;
    }
}
