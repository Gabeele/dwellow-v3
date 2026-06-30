<?php

use App\Enums\ApplicationStatus;
use App\Enums\OccupancyStatus;
use App\Models\Agent;
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

test('the dashboard exposes a total applications count linking to the applications page', function () {
    $landlord = User::factory()->landlord()->create();
    $property = Property::factory()->for($landlord, 'landlord')->multiUnit()->create();
    $unit = Unit::factory()->for($property)->create();
    $link = ApplicationLink::factory()->for($unit)->create();

    // Two new + one reviewed application all count toward the running total.
    Application::factory()->for($link, 'applicationLink')->count(2)->create();
    Application::factory()->for($link, 'applicationLink')->create([
        'status' => ApplicationStatus::Reviewing,
    ]);

    // Another landlord's application must not inflate the total.
    $otherLandlord = User::factory()->landlord()->create();
    $otherProperty = Property::factory()->for($otherLandlord, 'landlord')->multiUnit()->create();
    $otherLink = ApplicationLink::factory()->for(Unit::factory()->for($otherProperty)->create())->create();
    Application::factory()->for($otherLink, 'applicationLink')->create();

    $this->actingAs($landlord)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('stats.total_applications', 3)
            ->where('stats.new_applications', 2)
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
            ->where('agents', [])
        );
});

test('the dashboard surfaces the landlord\'s recent agent runs newest first', function () {
    $landlord = User::factory()->landlord()->create();
    $property = Property::factory()->for($landlord, 'landlord')->multiUnit()->create();
    $unit = Unit::factory()->for($property)->create();
    $link = ApplicationLink::factory()->for($unit)->create();

    $older = Application::factory()->for($link, 'applicationLink')->create();
    $olderAgent = Agent::factory()->forApplication($older)->completed()->create();

    $newer = Application::factory()->for($link, 'applicationLink')->create();
    $newerAgent = Agent::factory()->forApplication($newer)->processing()->create();

    $this->actingAs($landlord)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('agents', 2)
            // Newest first.
            ->where('agents.0.id', $newerAgent->id)
            ->where('agents.0.status', 'processing')
            ->where('agents.0.type', 'score')
            ->where('agents.0.subject_label', $newer->agentLabel())
            ->where('agents.0.url', $newer->agentUrl())
            ->where('agents.1.id', $olderAgent->id)
            ->where('agents.1.status', 'completed')
        );
});

test('the dashboard agents dataset is scoped to the current landlord', function () {
    $landlord = User::factory()->landlord()->create();
    $unit = Unit::factory()
        ->for(Property::factory()->for($landlord, 'landlord')->multiUnit()->create())
        ->create();
    $mine = Application::factory()
        ->for(ApplicationLink::factory()->for($unit)->create(), 'applicationLink')
        ->create();
    Agent::factory()->forApplication($mine)->completed()->create();

    // Another landlord's agent must not leak into this dashboard.
    $otherLandlord = User::factory()->landlord()->create();
    $otherUnit = Unit::factory()
        ->for(Property::factory()->for($otherLandlord, 'landlord')->multiUnit()->create())
        ->create();
    $theirs = Application::factory()
        ->for(ApplicationLink::factory()->for($otherUnit)->create(), 'applicationLink')
        ->create();
    Agent::factory()->forApplication($theirs)->completed()->create();

    $this->actingAs($landlord)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('agents', 1)
            ->where('agents.0.subject_label', $mine->agentLabel())
        );
});

test('the dashboard agents prop reloads in isolation for polling', function () {
    $landlord = User::factory()->landlord()->create();
    $unit = Unit::factory()
        ->for(Property::factory()->for($landlord, 'landlord')->multiUnit()->create())
        ->create();
    $application = Application::factory()
        ->for(ApplicationLink::factory()->for($unit)->create(), 'applicationLink')
        ->create();
    Agent::factory()->forApplication($application)->processing()->create();

    $this->withoutVite();

    // A partial reload (the poll the Agents table makes) asks only for `agents`;
    // the stats closure must not be evaluated or returned. A partial reload
    // responds with bare Inertia JSON, so assert on the payload directly.
    $this->actingAs($landlord)
        ->get(route('dashboard'), partialReloadHeaders('Dashboard', 'agents'))
        ->assertOk()
        ->assertJsonPath('component', 'Dashboard')
        ->assertJsonCount(1, 'props.agents')
        ->assertJsonMissingPath('props.stats');
});
