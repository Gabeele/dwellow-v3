<?php

use App\Models\Property;
use App\Observers\PropertyObserver;
use App\Screening\DefaultApplicationForm;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('provisions a single backing unit with a default form for a whole rental', function () {
    $property = Property::factory()->whole()->create();

    expect($property->units()->count())->toBe(1);

    $unit = $property->units()->first();
    expect($unit->label)->toBe($property->name);
    expect($unit->bedrooms)->toBe($property->bedrooms);
    expect($unit->applicationForm)->not->toBeNull();
    expect($unit->applicationForm->sections)->toEqual(DefaultApplicationForm::sections());
});

it('does not auto-provision a unit for a multi-unit property', function () {
    $property = Property::factory()->multiUnit()->create();

    expect($property->units()->count())->toBe(0);
});

it('does not add a second backing unit when the created hook fires again', function () {
    $property = Property::factory()->whole()->create();

    (new PropertyObserver)->created($property);

    expect($property->units()->count())->toBe(1);
});
