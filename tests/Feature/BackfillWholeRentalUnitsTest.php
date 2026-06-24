<?php

use App\Models\Property;
use App\Screening\DefaultApplicationForm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

/**
 * Simulate a legacy whole rental created before the PropertyObserver by
 * dropping the backing unit the observer auto-provisions on creation.
 */
function unitlessWholeProperty(): Property
{
    $property = Property::factory()->whole()->create();
    $property->units()->delete();

    return $property->fresh();
}

it('provisions a backing unit with a default form for a unit-less whole rental', function () {
    $property = unitlessWholeProperty();
    expect($property->units()->count())->toBe(0);

    Artisan::call('properties:backfill-backing-units');

    expect($property->units()->count())->toBe(1);

    $unit = $property->units()->first();
    expect($unit->applicationForm)->not->toBeNull();
    expect($unit->applicationForm->sections)->toEqual(DefaultApplicationForm::sections());
});

it('is idempotent — a second run adds no further units', function () {
    $property = unitlessWholeProperty();

    Artisan::call('properties:backfill-backing-units');
    Artisan::call('properties:backfill-backing-units');

    expect($property->units()->count())->toBe(1);
});

it('leaves whole rentals that already have a unit untouched', function () {
    $property = Property::factory()->whole()->create();
    $existingUnitId = $property->units()->first()->id;

    Artisan::call('properties:backfill-backing-units');

    expect($property->units()->count())->toBe(1);
    expect($property->units()->first()->id)->toBe($existingUnitId);
});

it('does not provision units for multi-unit properties', function () {
    $property = Property::factory()->multiUnit()->create();

    Artisan::call('properties:backfill-backing-units');

    expect($property->units()->count())->toBe(0);
});
