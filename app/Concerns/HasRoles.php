<?php

namespace App\Concerns;

use App\Enums\Role;
use App\Models\RoleUser;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * Adds explicit, multi-role support to a model via the `role_user` pivot.
 *
 * A user may hold any combination of roles (e.g. both Landlord and Tenant).
 */
trait HasRoles
{
    /**
     * The role assignments for this user.
     *
     * @return HasMany<RoleUser, $this>
     */
    public function roles(): HasMany
    {
        return $this->hasMany(RoleUser::class);
    }

    /**
     * Determine whether the user has the given role.
     */
    public function hasRole(Role $role): bool
    {
        return $this->roles->contains('role', $role);
    }

    /**
     * Assign one or more roles to the user, ignoring duplicates.
     */
    public function assignRole(Role ...$roles): void
    {
        foreach ($roles as $role) {
            $this->roles()->firstOrCreate(['role' => $role]);
        }

        $this->unsetRelation('roles');
    }

    /**
     * Remove a role from the user.
     */
    public function removeRole(Role $role): void
    {
        $this->roles()->where('role', $role)->delete();

        $this->unsetRelation('roles');
    }

    /**
     * The user's roles as a collection of {@see Role} enums.
     *
     * @return Collection<int, Role>
     */
    public function roleEnums(): Collection
    {
        return $this->roles->pluck('role');
    }

    /**
     * Determine whether the user can act as a landlord.
     */
    public function isLandlord(): bool
    {
        return $this->hasRole(Role::Landlord);
    }

    /**
     * Determine whether the user can act as a tenant.
     */
    public function isTenant(): bool
    {
        return $this->hasRole(Role::Tenant);
    }
}
