<?php

use App\Models\Application;
use App\Models\ApplicationLink;
use App\Models\Property;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('the property show page renders with its units for the owning landlord', function () {
    $landlord = User::factory()->landlord()->create();
    $property = Property::factory()->multiUnit()->for($landlord, 'landlord')->create();
    Unit::factory()->count(2)->for($property)->create();

    $this->actingAs($landlord)
        ->get(route('properties.show', $property))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('properties/Show')
            ->has('property')
            ->has('property.units', 2),
        );
});

test('the property show page exposes each unit application links and applicant counts', function () {
    $landlord = User::factory()->landlord()->create();
    $property = Property::factory()->multiUnit()->for($landlord, 'landlord')->create();
    $unit = Unit::factory()->for($property)->create();

    $link = ApplicationLink::factory()->for($unit)->create(['label' => 'Kijiji post']);
    Application::factory()->count(2)->for($link, 'applicationLink')->create();

    $this->actingAs($landlord)
        ->get(route('properties.show', $property))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('properties/Show')
            ->where('property.units.0.applications_count', 2)
            ->has('property.units.0.application_links', 1)
            ->where('property.units.0.application_links.0.label', 'Kijiji post')
            ->where('property.units.0.application_links.0.applications_count', 2)
            ->where('property.units.0.application_links.0.public_url', fn (string $url) => str_contains($url, '/screening/'.$link->token)),
        );
});

test('a whole rental show page renders with an empty units array', function () {
    $landlord = User::factory()->landlord()->create();
    $property = Property::factory()->whole()->for($landlord, 'landlord')->create();

    $this->actingAs($landlord)
        ->get(route('properties.show', $property))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('properties/Show')
            ->has('property')
            ->has('property.units'),
        );
});
