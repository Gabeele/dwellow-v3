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
