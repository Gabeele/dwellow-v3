<?php

use App\Models\Property;
use App\Screening\BackingUnitProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('provisions a single backing unit for a whole rental', function () {
    $property = Property::factory()->whole()->create();
    $property->units()->delete();
    expect($property->units()->count())->toBe(0);

    $unit = BackingUnitProvisioner::ensure($property->fresh());

    expect($property->fresh()->units()->count())->toBe(1);
    expect($unit->label)->toBe($property->name);
});

it('is idempotent — a second ensure adds no further units', function () {
    $property = Property::factory()->whole()->create();
    $existingId = $property->units()->first()->id;

    $unit = BackingUnitProvisioner::ensure($property->fresh());

    expect($property->fresh()->units()->count())->toBe(1);
    expect($unit->id)->toBe($existingId);
});

it('reports that whole rentals require a backing unit but multi-unit properties do not', function () {
    expect(BackingUnitProvisioner::applies(Property::factory()->whole()->make()))->toBeTrue();
    expect(BackingUnitProvisioner::applies(Property::factory()->multiUnit()->make()))->toBeFalse();
});
