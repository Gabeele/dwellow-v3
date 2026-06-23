<?php

use App\Models\Application;
use App\Models\ApplicationLink;
use App\Models\Document;
use App\Models\Property;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

/**
 * Create a unit owned by the given landlord.
 */
function applicantUnitOwnedBy(User $landlord): Unit
{
    $property = Property::factory()->for($landlord, 'landlord')->create();

    return Unit::factory()->for($property)->create();
}

test('the owning landlord sees their units applications newest first', function () {
    $landlord = User::factory()->landlord()->create();
    $unit = applicantUnitOwnedBy($landlord);
    $link = ApplicationLink::factory()->for($unit)->create();

    $older = Application::factory()->for($link, 'applicationLink')->create([
        'applicant_first_name' => 'Olive',
        'submitted_at' => now()->subDay(),
    ]);
    $newer = Application::factory()->for($link, 'applicationLink')->create([
        'applicant_first_name' => 'Nadia',
        'submitted_at' => now(),
    ]);

    $this->withoutVite();

    $this->actingAs($landlord)
        ->get(route('units.applicants.index', $unit))
        ->assertInertia(fn (Assert $page) => $page
            ->component('screening/applicants/Index')
            ->has('unit')
            ->has('applications', 2)
            ->where('applications.0.id', $newer->id)
            ->where('applications.1.id', $older->id),
        );
});

test('an applications document count is exposed', function () {
    $landlord = User::factory()->landlord()->create();
    $unit = applicantUnitOwnedBy($landlord);
    $link = ApplicationLink::factory()->for($unit)->create();

    $application = Application::factory()->for($link, 'applicationLink')->create();
    $application->documents()->saveMany(Document::factory()->count(2)->make());

    $this->withoutVite();

    $this->actingAs($landlord)
        ->get(route('units.applicants.index', $unit))
        ->assertInertia(fn (Assert $page) => $page
            ->where('applications.0.documents_count', 2),
        );
});

test('the landlord does not see another units applications', function () {
    $landlord = User::factory()->landlord()->create();
    $unit = applicantUnitOwnedBy($landlord);

    $otherUnit = applicantUnitOwnedBy($landlord);
    $otherLink = ApplicationLink::factory()->for($otherUnit)->create();
    Application::factory()->for($otherLink, 'applicationLink')->create();

    $this->withoutVite();

    $this->actingAs($landlord)
        ->get(route('units.applicants.index', $unit))
        ->assertInertia(fn (Assert $page) => $page->has('applications', 0));
});

test('a non-owner cannot view another landlords applicants', function () {
    $landlord = User::factory()->landlord()->create();
    $unit = Unit::factory()->create();

    $this->actingAs($landlord)
        ->get(route('units.applicants.index', $unit))
        ->assertForbidden();
});
