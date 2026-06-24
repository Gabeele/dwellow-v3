<?php

namespace App\Filament\Resources\Users\Concerns;

use App\Enums\Role;
use App\Models\User;
use RuntimeException;

trait SyncsUserRoles
{
    /**
     * Sync the saved record's roles from the (non-dehydrated) form selection.
     */
    protected function syncSelectedRoles(): void
    {
        $roles = array_map(Role::from(...), $this->data['roles'] ?? []);

        $this->userRecord()->syncRoles(...$roles);
    }

    /**
     * The page's record, narrowed to the User model these pages always operate on.
     */
    protected function userRecord(): User
    {
        $record = $this->record;

        if (! $record instanceof User) {
            throw new RuntimeException('Expected the page record to be a User.');
        }

        return $record;
    }
}
