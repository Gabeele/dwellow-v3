<?php

use App\Models\ApplicationForm;
use App\Models\Unit;
use App\Observers\UnitObserver;
use App\Screening\DefaultApplicationForm;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('provisions the dwellow default application form when a unit is created', function () {
    $unit = Unit::factory()->create();

    expect($unit->applicationForm)->not->toBeNull();
    expect($unit->applicationForm->sections)->toEqual(DefaultApplicationForm::sections());
});

it('does not duplicate the form when the created hook fires again', function () {
    $unit = Unit::factory()->create();

    (new UnitObserver)->created($unit);

    expect(ApplicationForm::where('unit_id', $unit->id)->count())->toBe(1);
});

it('seeds the default form via applicationFormOrDefault when one is missing', function () {
    $unit = Unit::factory()->create();
    $unit->applicationForm()->delete();

    $form = $unit->applicationFormOrDefault();

    expect($form->sections)->toEqual(DefaultApplicationForm::sections());
    expect(ApplicationForm::where('unit_id', $unit->id)->count())->toBe(1);
});

it('returns the existing form via applicationFormOrDefault without duplicating it', function () {
    $unit = Unit::factory()->create();
    $existing = $unit->applicationForm;

    $form = $unit->applicationFormOrDefault();

    expect($form->id)->toBe($existing->id);
    expect(ApplicationForm::where('unit_id', $unit->id)->count())->toBe(1);
});
