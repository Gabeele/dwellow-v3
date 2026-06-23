<?php

namespace App\Policies;

use App\Models\ApplicationForm;
use App\Models\User;

class ApplicationFormPolicy
{
    /**
     * Determine whether the user can view the application form.
     */
    public function view(User $user, ApplicationForm $applicationForm): bool
    {
        return $this->owns($user, $applicationForm);
    }

    /**
     * Determine whether the user can update the application form.
     */
    public function update(User $user, ApplicationForm $applicationForm): bool
    {
        return $this->owns($user, $applicationForm);
    }

    /**
     * A landlord may only act on application forms for units of properties they own.
     */
    private function owns(User $user, ApplicationForm $applicationForm): bool
    {
        return $user->isLandlord() && $applicationForm->unit->property->landlord_id === $user->id;
    }
}
