<?php

use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * A valid payload for a whole-property rental.
 *
 * @return array<string, mixed>
 */
function wholePropertyPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Test Property',
        'address_line1' => '1 Test Street',
        'city' => 'Testville',
        'region' => 'ON',
        'postal_code' => 'A1A1A1',
        'country' => 'CA',
        'type' => 'house',
        'rental_type' => 'whole',
        'bedrooms' => 3,
        'bathrooms' => 2,
        'rent_amount' => 1500,
        'status' => 'available',
    ], $overrides);
}

test('a landlord sees only their own properties on the index', function () {
    $landlord = User::factory()->landlord()->create();
    $own = Property::factory()->for($landlord, 'landlord')->create();
    $other = Property::factory()->create();

    $this->actingAs($landlord)
        ->get(route('properties.index'))
        ->assertOk();

    expect($landlord->properties()->pluck('id')->all())
        ->toContain($own->id)
        ->not->toContain($other->id);
});

test('a tenant-only user is forbidden from the properties area', function () {
    $tenant = User::factory()->tenant()->create();

    $this->actingAs($tenant)->get(route('properties.index'))->assertForbidden();
    $this->actingAs($tenant)->get(route('properties.create'))->assertForbidden();
    $this->actingAs($tenant)->post(route('properties.store'), wholePropertyPayload())->assertForbidden();
});

test('a landlord can create a property', function () {
    $landlord = User::factory()->landlord()->create();

    $this->actingAs($landlord)
        ->post(route('properties.store'), wholePropertyPayload())
        ->assertRedirect();

    $this->assertDatabaseHas('properties', [
        'landlord_id' => $landlord->id,
        'address_line1' => '1 Test Street',
        'rental_type' => 'whole',
    ]);
});

test('whole-rental detail fields are rejected for a multi-unit property', function () {
    $landlord = User::factory()->landlord()->create();

    // Rentable details belong on units, not on a multi-unit property itself.
    $this->actingAs($landlord)
        ->post(route('properties.store'), wholePropertyPayload([
            'rental_type' => 'multi_unit',
        ]))
        ->assertSessionHasErrors(['bedrooms', 'bathrooms', 'rent_amount']);
});

test('a landlord cannot view another landlords property', function () {
    $landlord = User::factory()->landlord()->create();
    $property = Property::factory()->create();

    $this->actingAs($landlord)
        ->get(route('properties.show', $property))
        ->assertForbidden();
});

test('a landlord cannot update or delete another landlords property', function () {
    $landlord = User::factory()->landlord()->create();
    $property = Property::factory()->create();

    $this->actingAs($landlord)
        ->put(route('properties.update', $property), wholePropertyPayload())
        ->assertForbidden();

    $this->actingAs($landlord)
        ->delete(route('properties.destroy', $property))
        ->assertForbidden();

    $this->assertDatabaseHas('properties', ['id' => $property->id]);
});

test('a landlord can delete their own property', function () {
    $landlord = User::factory()->landlord()->create();
    $property = Property::factory()->for($landlord, 'landlord')->create();

    $this->actingAs($landlord)
        ->delete(route('properties.destroy', $property))
        ->assertRedirect(route('properties.index'));

    $this->assertDatabaseMissing('properties', ['id' => $property->id]);
});
