<?php

use App\Models\ApplicationForm;
use App\Models\Unit;
use App\Screening\DefaultApplicationForm;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lets a unit have one application form', function () {
    $unit = Unit::factory()->create();
    $form = ApplicationForm::factory()->forUnit($unit)->create();

    expect($unit->applicationForm->is($form))->toBeTrue();
    expect($form->unit->is($unit))->toBeTrue();
});

it('round-trips the fields json as an array', function () {
    $form = ApplicationForm::factory()->create();

    expect($form->fresh()->fields)
        ->toBeArray()
        ->toEqual(DefaultApplicationForm::fields());
});

it('enforces one form per unit', function () {
    $unit = Unit::factory()->create();
    ApplicationForm::factory()->forUnit($unit)->create();

    expect(fn () => ApplicationForm::factory()->forUnit($unit)->create())
        ->toThrow(QueryException::class);
});
