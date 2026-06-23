<?php

use App\Models\User;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['admin.emails' => ['founder@dwellow.app']]);
    $this->panel = Mockery::mock(Panel::class);
});

it('allows allowlisted emails into the admin panel', function () {
    $user = User::factory()->create(['email' => 'founder@dwellow.app']);

    expect($user->canAccessPanel($this->panel))->toBeTrue();
});

it('forbids non-allowlisted emails from the admin panel', function () {
    $user = User::factory()->create(['email' => 'tenant@example.com']);

    expect($user->canAccessPanel($this->panel))->toBeFalse();
});

it('allows admin-role users into the admin panel', function () {
    $user = User::factory()->admin()->create(['email' => 'someadmin@example.com']);

    expect($user->canAccessPanel($this->panel))->toBeTrue();
});

it('forbids landlords and tenants without the admin role', function () {
    $user = User::factory()->landlord()->tenant()->create(['email' => 'renter@example.com']);

    expect($user->canAccessPanel($this->panel))->toBeFalse();
});
