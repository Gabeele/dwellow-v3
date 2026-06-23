<?php

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;

uses(RefreshDatabase::class);

test('a user can hold multiple roles and report them', function () {
    $user = User::factory()->create();

    $user->assignRole(Role::Landlord, Role::Tenant);

    expect($user->isLandlord())->toBeTrue()
        ->and($user->isTenant())->toBeTrue()
        ->and($user->roles)->toHaveCount(2);
});

test('assigning the same role twice does not duplicate it', function () {
    $user = User::factory()->create();

    $user->assignRole(Role::Landlord);
    $user->assignRole(Role::Landlord);

    expect($user->roles()->where('role', Role::Landlord)->count())->toBe(1);
});

test('removing a role revokes that capability', function () {
    $user = User::factory()->landlord()->tenant()->create();

    $user->removeRole(Role::Tenant);

    expect($user->isLandlord())->toBeTrue()
        ->and($user->isTenant())->toBeFalse();
});

test('registration assigns the chosen roles', function () {
    $this->skipUnlessFortifyHas(Features::registration());

    $this->post(route('register.store'), [
        'name' => 'New Landlord',
        'email' => 'newlandlord@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'roles' => ['landlord', 'tenant'],
    ]);

    $user = User::where('email', 'newlandlord@example.com')->firstOrFail();

    expect($user->isLandlord())->toBeTrue()
        ->and($user->isTenant())->toBeTrue();
});

test('registration without a role choice defaults to tenant', function () {
    $this->skipUnlessFortifyHas(Features::registration());

    $this->post(route('register.store'), [
        'name' => 'Default User',
        'email' => 'default@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $user = User::where('email', 'default@example.com')->firstOrFail();

    expect($user->isTenant())->toBeTrue()
        ->and($user->isLandlord())->toBeFalse();
});
