<?php

use App\Models\ApplicationForm;
use App\Models\Unit;
use App\Screening\DefaultApplicationForm;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lets a unit have one application form', function () {
    $unit = Unit::factory()->create();
    $form = $unit->applicationForm;

    expect($form)->not->toBeNull();
    expect($form->unit->is($unit))->toBeTrue();
});

it('round-trips the fields json as an array', function () {
    $form = Unit::factory()->create()->applicationForm;

    expect($form->fresh()->fields)
        ->toBeArray()
        ->toEqual(DefaultApplicationForm::fields());
});

it('enforces one form per unit', function () {
    // The unit already has its auto-provisioned form, so a second insert collides.
    $unit = Unit::factory()->create();

    expect(fn () => ApplicationForm::factory()->forUnit($unit)->create())
        ->toThrow(QueryException::class);
});
