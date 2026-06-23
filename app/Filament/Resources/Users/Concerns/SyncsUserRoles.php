<?php

namespace App\Filament\Resources\Users\Concerns;

use App\Enums\Role;

trait SyncsUserRoles
{
    /**
     * Sync the saved record's roles from the (non-dehydrated) form selection.
     */
    protected function syncSelectedRoles(): void
    {
        $roles = array_map(Role::from(...), $this->data['roles'] ?? []);

        $this->record->syncRoles(...$roles);
    }
}
