<?php

use App\Models\Property;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * A valid payload for a unit.
 *
 * @return array<string, mixed>
 */
function unitPayload(array $overrides = []): array
{
    return array_merge([
        'label' => 'Unit A',
        'bedrooms' => 2,
        'bathrooms' => 1,
        'rent_amount' => 1200,
        'status' => 'available',
    ], $overrides);
}

test('a landlord can add a unit to their multi-unit property', function () {
    $landlord = User::factory()->landlord()->create();
    $property = Property::factory()->multiUnit()->for($landlord, 'landlord')->create();

    $this->actingAs($landlord)
        ->post(route('properties.units.store', $property), unitPayload())
        ->assertRedirect(route('properties.show', $property));

    $this->assertDatabaseHas('units', [
        'property_id' => $property->id,
        'label' => 'Unit A',
    ]);
});

test('unit labels must be unique within a property', function () {
    $landlord = User::factory()->landlord()->create();
    $property = Property::factory()->multiUnit()->for($landlord, 'landlord')->create();
    Unit::factory()->for($property)->create(['label' => 'Unit A']);

    $this->actingAs($landlord)
        ->post(route('properties.units.store', $property), unitPayload(['label' => 'Unit A']))
        ->assertSessionHasErrors('label');
});

test('a landlord cannot add a unit to another landlords property', function () {
    $landlord = User::factory()->landlord()->create();
    $property = Property::factory()->multiUnit()->create();

    $this->actingAs($landlord)
        ->post(route('properties.units.store', $property), unitPayload())
        ->assertForbidden();

    $this->assertDatabaseMissing('units', ['property_id' => $property->id]);
});

test('a landlord cannot update or delete a unit of another landlords property', function () {
    $landlord = User::factory()->landlord()->create();
    $unit = Unit::factory()->create();

    $this->actingAs($landlord)
        ->put(route('units.update', $unit), unitPayload(['label' => 'Changed']))
        ->assertForbidden();

    $this->actingAs($landlord)
        ->delete(route('units.destroy', $unit))
        ->assertForbidden();

    $this->assertDatabaseHas('units', ['id' => $unit->id]);
});

test('a landlord can update and delete their own unit', function () {
    $landlord = User::factory()->landlord()->create();
    $property = Property::factory()->multiUnit()->for($landlord, 'landlord')->create();
    $unit = Unit::factory()->for($property)->create(['label' => 'Unit A']);

    $this->actingAs($landlord)
        ->put(route('units.update', $unit), unitPayload(['label' => 'Unit B']))
        ->assertRedirect(route('properties.show', $property));

    $this->assertDatabaseHas('units', ['id' => $unit->id, 'label' => 'Unit B']);

    $this->actingAs($landlord)
        ->delete(route('units.destroy', $unit))
        ->assertRedirect(route('properties.show', $property));

    $this->assertDatabaseMissing('units', ['id' => $unit->id]);
});
