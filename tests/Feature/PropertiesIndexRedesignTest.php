<?php

use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('the properties index renders the redesigned component with occupancy aggregates', function () {
    $landlord = User::factory()->landlord()->create();
    Property::factory()->for($landlord, 'landlord')->create();

    $this->actingAs($landlord)
        ->get(route('properties.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('properties/Index')
            ->has('properties')
            ->has('properties.0.units_count')
            ->has('properties.0.occupied_units_count')
            ->has('properties.0.available_units_count')
        );
});

test('the properties index renders for a landlord without properties', function () {
    $landlord = User::factory()->landlord()->create();

    $this->actingAs($landlord)
        ->get(route('properties.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('properties/Index')
            ->has('properties', 0)
        );
});
