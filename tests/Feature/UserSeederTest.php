<?php

use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('seeded allowlisted admins receive the admin role', function () {
    config(['admin.emails' => ['founder@dwellow.app']]);

    $this->seed(UserSeeder::class);

    $admin = User::where('email', 'founder@dwellow.app')->firstOrFail();

    expect($admin->isAdmin())->toBeTrue();
});

test('the system user is not granted the admin role', function () {
    config(['admin.emails' => []]);

    $this->seed(UserSeeder::class);

    $system = User::where('email', 'system@dwellow.app')->firstOrFail();

    expect($system->isAdmin())->toBeFalse();
});
