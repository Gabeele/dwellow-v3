<?php

use App\Models\Property;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('the property create form renders with options', function () {
    $landlord = User::factory()->landlord()->create();

    $this->actingAs($landlord)
        ->get(route('properties.create'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('properties/Create')
            ->has('options'),
        );
});

test('the property edit form renders with the property and options', function () {
    $landlord = User::factory()->landlord()->create();
    $property = Property::factory()->for($landlord, 'landlord')->create();

    $this->actingAs($landlord)
        ->get(route('properties.edit', $property))
        ->assertInertia(fn (Assert $page) => $page
            ->component('properties/Edit')
            ->has('property')
            ->has('options'),
        );
});

test('the unit create form renders with the property and statuses', function () {
    $landlord = User::factory()->landlord()->create();
    $property = Property::factory()->multiUnit()->for($landlord, 'landlord')->create();

    $this->actingAs($landlord)
        ->get(route('properties.units.create', $property))
        ->assertInertia(fn (Assert $page) => $page
            ->component('properties/units/Create')
            ->has('property')
            ->has('statuses'),
        );
});

test('the unit edit form renders with the property, unit and statuses', function () {
    $landlord = User::factory()->landlord()->create();
    $property = Property::factory()->multiUnit()->for($landlord, 'landlord')->create();
    $unit = Unit::factory()->for($property)->create();

    $this->actingAs($landlord)
        ->get(route('units.edit', $unit))
        ->assertInertia(fn (Assert $page) => $page
            ->component('properties/units/Edit')
            ->has('property')
            ->has('unit')
            ->has('statuses'),
        );
});
