<?php

use App\Enums\Role;

test('the admin role reports its label', function () {
    expect(Role::Admin->label())->toBe('Admin');
});
