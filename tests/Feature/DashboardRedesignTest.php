<?php

use App\Enums\OccupancyStatus;
use App\Models\Property;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('a landlord sees real portfolio stats on the dashboard', function () {
    $landlord = User::factory()->landlord()->create();

    // A whole rental that is occupied → 1 space, 1 occupied, 0 available.
    Property::factory()->for($landlord, 'landlord')->whole()->create([
        'status' => OccupancyStatus::Occupied,
    ]);

    // A multi-unit property with 3 units: 2 occupied, 1 available.
    $multiUnit = Property::factory()->for($landlord, 'landlord')->multiUnit()->create();
    Unit::factory()->for($multiUnit)->create(['status' => OccupancyStatus::Occupied]);
    Unit::factory()->for($multiUnit)->create(['status' => OccupancyStatus::Occupied]);
    Unit::factory()->for($multiUnit)->create(['status' => OccupancyStatus::Available]);

    $this->actingAs($landlord)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('stats')
            ->where('stats.properties', 2)
            ->where('stats.units', 4)
            ->where('stats.occupied', 3)
            ->where('stats.available', 1)
        );
});

test('a verified non-landlord user sees no stats', function () {
    $user = User::factory()->create();

    expect($user->email_verified_at)->not->toBeNull();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('stats', null)
        );
});
