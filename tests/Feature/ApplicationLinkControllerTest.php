<?php

use App\Models\Property;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Create a unit owned by the given landlord. The unit observer provisions its
 * single application link on creation.
 */
function ownedUnit(User $landlord): Unit
{
    $property = Property::factory()->for($landlord, 'landlord')->create();

    return Unit::factory()->for($property)->create();
}

test('a new unit is provisioned with a single open application link', function () {
    $landlord = User::factory()->landlord()->create();
    $unit = ownedUnit($landlord);

    $link = $unit->applicationLinks()->sole();

    expect($unit->applicationLinks()->count())->toBe(1)
        ->and($link->token)->not->toBeEmpty()
        ->and($link->is_accepting)->toBeTrue()
        ->and($link->isOpen())->toBeTrue();
});

test('the owning landlord can turn the link off', function () {
    $landlord = User::factory()->landlord()->create();
    $unit = ownedUnit($landlord);
    $link = $unit->applicationLinkOrDefault();

    $this->actingAs($landlord)
        ->put(route('links.update', $link), ['is_accepting' => false])
        ->assertRedirect();

    expect($link->refresh()->is_accepting)->toBeFalse()
        ->and($link->isOpen())->toBeFalse();
});

test('the owning landlord can turn the link back on', function () {
    $landlord = User::factory()->landlord()->create();
    $unit = ownedUnit($landlord);
    $link = $unit->applicationLinkOrDefault();
    $link->update(['is_accepting' => false]);

    $this->actingAs($landlord)
        ->put(route('links.update', $link), ['is_accepting' => true])
        ->assertRedirect();

    expect($link->refresh()->is_accepting)->toBeTrue()
        ->and($link->isOpen())->toBeTrue();
});

test('is_accepting is required when toggling a link', function () {
    $landlord = User::factory()->landlord()->create();
    $unit = ownedUnit($landlord);
    $link = $unit->applicationLinkOrDefault();

    $this->actingAs($landlord)
        ->put(route('links.update', $link), [])
        ->assertSessionHasErrors('is_accepting');
});

test('a non-owner cannot toggle a link', function () {
    $owner = User::factory()->landlord()->create();
    $unit = ownedUnit($owner);
    $link = $unit->applicationLinkOrDefault();

    $intruder = User::factory()->landlord()->create();

    $this->actingAs($intruder)
        ->put(route('links.update', $link), ['is_accepting' => false])
        ->assertForbidden();
});
