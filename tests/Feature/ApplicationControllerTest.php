<?php

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\ApplicationLink;
use App\Models\Document;
use App\Models\Property;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
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

test('the owning landlord sees an applications snapshot and documents', function () {
    $landlord = User::factory()->landlord()->create();
    $unit = applicantUnitOwnedBy($landlord);
    $link = ApplicationLink::factory()->for($unit)->create();

    $application = Application::factory()->for($link, 'applicationLink')->create([
        'applicant_first_name' => 'Priya',
        'applicant_email' => 'priya@example.com',
        'form_snapshot' => [
            ['key' => 'first_name', 'type' => 'short_text', 'label' => 'First name', 'required' => true, 'help' => null, 'options' => null],
            ['key' => 'photo_id', 'type' => 'file', 'label' => 'Photo ID', 'required' => true, 'help' => null, 'options' => null],
        ],
        'answers' => ['first_name' => 'Priya', 'photo_id' => 'id.png'],
    ]);
    $document = Document::factory()->for($application)->create(['field_key' => 'photo_id']);

    $this->withoutVite();

    $this->actingAs($landlord)
        ->get(route('applicants.show', $application))
        ->assertInertia(fn (Assert $page) => $page
            ->component('screening/applicants/Show')
            ->where('application.id', $application->id)
            ->has('application.form_snapshot', 2)
            ->where('application.answers.first_name', 'Priya')
            ->has('documents', 1)
            ->where('documents.0.id', $document->id),
        );
});

test('a non-owner cannot view another landlords application detail', function () {
    $landlord = User::factory()->landlord()->create();
    $otherUnit = Unit::factory()->create();
    $otherLink = ApplicationLink::factory()->for($otherUnit)->create();
    $application = Application::factory()->for($otherLink, 'applicationLink')->create();

    $this->actingAs($landlord)
        ->get(route('applicants.show', $application))
        ->assertForbidden();
});

test('the owning landlord can update an applications status and notes', function () {
    $landlord = User::factory()->landlord()->create();
    $unit = applicantUnitOwnedBy($landlord);
    $link = ApplicationLink::factory()->for($unit)->create();

    $application = Application::factory()->for($link, 'applicationLink')->create([
        'status' => ApplicationStatus::Reviewing,
        'landlord_notes' => null,
    ]);

    $this->actingAs($landlord)
        ->from(route('applicants.show', $application))
        ->put(route('applicants.update', $application), [
            'status' => ApplicationStatus::Approved->value,
            'landlord_notes' => 'Strong references, approved.',
        ])
        ->assertRedirect(route('applicants.show', $application));

    $application->refresh();

    expect($application->status)->toBe(ApplicationStatus::Approved)
        ->and($application->landlord_notes)->toBe('Strong references, approved.');
});

test('an application cannot be moved to an invalid status', function () {
    $landlord = User::factory()->landlord()->create();
    $unit = applicantUnitOwnedBy($landlord);
    $link = ApplicationLink::factory()->for($unit)->create();
    $application = Application::factory()->for($link, 'applicationLink')->create();

    $this->actingAs($landlord)
        ->put(route('applicants.update', $application), ['status' => 'archived'])
        ->assertSessionHasErrors('status');
});

test('a non-owner cannot update another landlords application', function () {
    $landlord = User::factory()->landlord()->create();
    $otherUnit = Unit::factory()->create();
    $otherLink = ApplicationLink::factory()->for($otherUnit)->create();
    $application = Application::factory()->for($otherLink, 'applicationLink')->create();

    $this->actingAs($landlord)
        ->put(route('applicants.update', $application), [
            'status' => ApplicationStatus::Approved->value,
        ])
        ->assertForbidden();
});

test('the owning landlord can delete an application and its documents', function () {
    Storage::fake('local');

    $landlord = User::factory()->landlord()->create();
    $unit = applicantUnitOwnedBy($landlord);
    $link = ApplicationLink::factory()->for($unit)->create();
    $application = Application::factory()->for($link, 'applicationLink')->create();

    $path = "applications/{$application->id}/id.png";
    Storage::disk('local')->put($path, 'fake-bytes');
    $document = Document::factory()->for($application)->create([
        'disk' => 'local',
        'path' => $path,
    ]);

    $this->actingAs($landlord)
        ->delete(route('applicants.destroy', $application))
        ->assertRedirect(route('units.applicants.index', $unit));

    $this->assertModelMissing($application);
    $this->assertDatabaseMissing('documents', ['id' => $document->id]);
    Storage::disk('local')->assertMissing($path);
});

test('a non-owner cannot delete another landlords application', function () {
    $landlord = User::factory()->landlord()->create();
    $otherUnit = Unit::factory()->create();
    $otherLink = ApplicationLink::factory()->for($otherUnit)->create();
    $application = Application::factory()->for($otherLink, 'applicationLink')->create();

    $this->actingAs($landlord)
        ->delete(route('applicants.destroy', $application))
        ->assertForbidden();

    $this->assertModelExists($application);
});
