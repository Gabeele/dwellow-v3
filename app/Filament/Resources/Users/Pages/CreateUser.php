<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\Concerns\SyncsUserRoles;
use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    use SyncsUserRoles;

    protected static string $resource = UserResource::class;

    protected function afterCreate(): void
    {
        $this->syncSelectedRoles();
    }
}
