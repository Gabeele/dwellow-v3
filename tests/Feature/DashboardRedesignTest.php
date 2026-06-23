<?php

use App\Enums\ApplicationStatus;
use App\Enums\OccupancyStatus;
use App\Models\Application;
use App\Models\ApplicationLink;
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

test('the dashboard surfaces new applicant activity scoped to the landlord', function () {
    $landlord = User::factory()->landlord()->create();
    $property = Property::factory()->for($landlord, 'landlord')->multiUnit()->create();

    $quietUnit = Unit::factory()->for($property)->create();
    $busyUnit = Unit::factory()->for($property)->create();

    // Two new applications on the busy unit, one on the quiet unit.
    $busyLink = ApplicationLink::factory()->for($busyUnit)->create();
    Application::factory()->for($busyLink, 'applicationLink')->count(2)->create();

    $quietLink = ApplicationLink::factory()->for($quietUnit)->create();
    Application::factory()->for($quietLink, 'applicationLink')->create();

    // A reviewed application no longer counts as "new".
    Application::factory()->for($busyLink, 'applicationLink')->create([
        'status' => ApplicationStatus::Reviewing,
    ]);

    // Another landlord's application must not leak into the count.
    $otherLandlord = User::factory()->landlord()->create();
    $otherProperty = Property::factory()->for($otherLandlord, 'landlord')->multiUnit()->create();
    $otherLink = ApplicationLink::factory()->for(Unit::factory()->for($otherProperty)->create())->create();
    Application::factory()->for($otherLink, 'applicationLink')->create();

    $this->actingAs($landlord)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('stats.new_applications', 3)
            ->where('stats.busiest_unit.id', $busyUnit->id)
            ->where('stats.busiest_unit.applications_count', 3)
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
