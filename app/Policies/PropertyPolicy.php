<?php

namespace App\Policies;

use App\Models\Property;
use App\Models\User;

class PropertyPolicy
{
    /**
     * Determine whether the user can view a list of their properties.
     */
    public function viewAny(User $user): bool
    {
        return $user->isLandlord();
    }

    /**
     * Determine whether the user can view the property.
     */
    public function view(User $user, Property $property): bool
    {
        return $this->owns($user, $property);
    }

    /**
     * Determine whether the user can create properties.
     */
    public function create(User $user): bool
    {
        return $user->isLandlord();
    }

    /**
     * Determine whether the user can update the property.
     */
    public function update(User $user, Property $property): bool
    {
        return $this->owns($user, $property);
    }

    /**
     * Determine whether the user can delete the property.
     */
    public function delete(User $user, Property $property): bool
    {
        return $this->owns($user, $property);
    }

    /**
     * A landlord may only act on properties they own.
     */
    private function owns(User $user, Property $property): bool
    {
        return $user->isLandlord() && $property->landlord_id === $user->id;
    }
}
