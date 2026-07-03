<?php

use App\Enums\OnboardingStep;
use App\Models\Application;
use App\Models\ApplicationLink;
use App\Models\Property;
use App\Models\Score;
use App\Models\Unit;
use App\Models\User;
use App\Onboarding\OnboardingState;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a landlord with no property needs to add one', function () {
    $landlord = User::factory()->landlord()->create();
    $state = new OnboardingState($landlord);

    expect($state->hasProperty())->toBeFalse()
        ->and($state->nextStep())->toBe(OnboardingStep::AddProperty)
        ->and($state->isActivated())->toBeFalse();
});

test('a landlord with a property but no active link needs to build an application', function () {
    $landlord = User::factory()->landlord()->create();
    $property = Property::factory()->multiUnit()->for($landlord, 'landlord')->create();
    $unit = Unit::factory()->for($property)->create();
    $unit->applicationLinks()->delete();

    $state = new OnboardingState($landlord);

    expect($state->hasProperty())->toBeTrue()
        ->and($state->hasActiveLink())->toBeFalse()
        ->and($state->nextStep())->toBe(OnboardingStep::BuildApplication)
        ->and($state->unitNeedingApplication()?->is($unit))->toBeTrue();
});

test('a landlord with an active link but no applicant needs to share it', function () {
    $landlord = User::factory()->landlord()->create();
    $property = Property::factory()->multiUnit()->for($landlord, 'landlord')->create();
    Unit::factory()->for($property)->create();

    $state = new OnboardingState($landlord);

    expect($state->hasActiveLink())->toBeTrue()
        ->and($state->nextStep())->toBe(OnboardingStep::ShareLink)
        ->and($state->isActivated())->toBeFalse();
});

test('a landlord with a scored applicant is activated', function () {
    $landlord = User::factory()->landlord()->create();
    $property = Property::factory()->multiUnit()->for($landlord, 'landlord')->create();
    $unit = Unit::factory()->for($property)->create();
    $link = ApplicationLink::factory()->for($unit)->create();
    $application = Application::factory()->for($link, 'applicationLink')->create(['unit_id' => $unit->id]);
    Score::factory()->for($application)->create();

    $state = new OnboardingState($landlord);

    expect($state->hasScoredApplicant())->toBeTrue()
        ->and($state->isActivated())->toBeTrue()
        ->and($state->nextStep())->toBe(OnboardingStep::Activated);
});

test('a revoked link does not count as active', function () {
    $landlord = User::factory()->landlord()->create();
    $property = Property::factory()->multiUnit()->for($landlord, 'landlord')->create();
    $unit = Unit::factory()->for($property)->create();
    $unit->applicationLink->update(['revoked_at' => now()]);

    $state = new OnboardingState($landlord);

    expect($state->hasActiveLink())->toBeFalse()
        ->and($state->nextStep())->toBe(OnboardingStep::BuildApplication);
});
