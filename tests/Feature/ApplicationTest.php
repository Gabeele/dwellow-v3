<?php

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\ApplicationLink;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('persists with array casts intact', function () {
    $application = Application::factory()->create();

    $fresh = $application->fresh();

    expect($fresh->answers)->toBeArray()
        ->and($fresh->form_snapshot)->toBeArray()
        ->and($fresh->form_snapshot)->not->toBeEmpty()
        ->and($fresh->status)->toBe(ApplicationStatus::New)
        ->and($fresh->submitted_at)->not->toBeNull();
});

it('belongs to its link and unit', function () {
    $application = Application::factory()->create();

    expect($application->applicationLink)->toBeInstanceOf(ApplicationLink::class)
        ->and($application->unit)->toBeInstanceOf(Unit::class)
        ->and($application->unit_id)->toBe($application->applicationLink->unit_id);
});

it('is included in a unit\'s applications', function () {
    $unit = Unit::factory()->create();
    $link = ApplicationLink::factory()->create(['unit_id' => $unit->id]);
    $application = Application::factory()->create([
        'application_link_id' => $link->id,
        'unit_id' => $unit->id,
    ]);

    expect($unit->applications()->pluck('id'))->toContain($application->id);
});
