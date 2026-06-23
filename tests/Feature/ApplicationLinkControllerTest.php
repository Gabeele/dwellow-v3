<?php

use App\Models\ApplicationLink;
use App\Models\Property;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Create a unit owned by the given landlord.
 */
function ownedUnit(User $landlord): Unit
{
    $property = Property::factory()->for($landlord, 'landlord')->create();

    return Unit::factory()->for($property)->create();
}

test('the owning landlord can create an application link', function () {
    $landlord = User::factory()->landlord()->create();
    $unit = ownedUnit($landlord);

    $this->actingAs($landlord)
        ->post(route('units.links.store', $unit), ['label' => 'Facebook post'])
        ->assertRedirect();

    $link = $unit->applicationLinks()->sole();

    expect($link->label)->toBe('Facebook post')
        ->and($link->token)->not->toBeEmpty()
        ->and($link->is_accepting)->toBeTrue()
        ->and($link->isOpen())->toBeTrue();
});

test('the owning landlord can toggle accepting off', function () {
    $landlord = User::factory()->landlord()->create();
    $unit = ownedUnit($landlord);
    $link = ApplicationLink::factory()->for($unit)->create();

    $this->actingAs($landlord)
        ->put(route('links.update', $link), ['is_accepting' => false])
        ->assertRedirect();

    expect($link->refresh()->is_accepting)->toBeFalse()
        ->and($link->isOpen())->toBeFalse();
});

test('the owning landlord can set an expiry', function () {
    $landlord = User::factory()->landlord()->create();
    $unit = ownedUnit($landlord);
    $link = ApplicationLink::factory()->for($unit)->create();

    $expiry = now()->addWeek()->startOfSecond();

    $this->actingAs($landlord)
        ->put(route('links.update', $link), ['expires_at' => $expiry->toIso8601String()])
        ->assertRedirect();

    expect($link->refresh()->expires_at->equalTo($expiry))->toBeTrue();
});

test('the owning landlord can revoke a link', function () {
    $landlord = User::factory()->landlord()->create();
    $unit = ownedUnit($landlord);
    $link = ApplicationLink::factory()->for($unit)->create();

    $this->actingAs($landlord)
        ->delete(route('links.destroy', $link))
        ->assertRedirect();

    $link->refresh();

    expect($link->revoked_at)->not->toBeNull()
        ->and($link->isOpen())->toBeFalse();
});

test('a non-owner cannot create, update, or revoke links', function () {
    $owner = User::factory()->landlord()->create();
    $unit = ownedUnit($owner);
    $link = ApplicationLink::factory()->for($unit)->create();

    $intruder = User::factory()->landlord()->create();

    $this->actingAs($intruder)
        ->post(route('units.links.store', $unit), ['label' => 'nope'])
        ->assertForbidden();

    $this->actingAs($intruder)
        ->put(route('links.update', $link), ['is_accepting' => false])
        ->assertForbidden();

    $this->actingAs($intruder)
        ->delete(route('links.destroy', $link))
        ->assertForbidden();
});
