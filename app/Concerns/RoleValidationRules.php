<?php

namespace App\Concerns;

use App\Enums\Role;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait RoleValidationRules
{
    /**
     * Get the validation rules used to validate a user's selected roles.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function roleRules(): array
    {
        return [
            'roles' => ['nullable', 'array'],
            'roles.*' => [Rule::enum(Role::class)->only([Role::Landlord, Role::Tenant])],
        ];
    }
}
