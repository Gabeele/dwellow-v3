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

test('the all-applications index lists every application across the landlords units', function () {
    $landlord = User::factory()->landlord()->create();

    $propertyA = Property::factory()->for($landlord, 'landlord')->create(['name' => 'Maple Court']);
    $unitA = Unit::factory()->for($propertyA)->create(['label' => 'Unit 1']);
    $linkA = ApplicationLink::factory()->for($unitA)->create();
    $appA = Application::factory()->for($linkA, 'applicationLink')->create([
        'submitted_at' => now()->subDay(),
    ]);

    $propertyB = Property::factory()->for($landlord, 'landlord')->create(['name' => 'Birch Flats']);
    $unitB = Unit::factory()->for($propertyB)->create(['label' => 'Unit 7']);
    $linkB = ApplicationLink::factory()->for($unitB)->create();
    $appB = Application::factory()->for($linkB, 'applicationLink')->create([
        'submitted_at' => now(),
    ]);

    $otherUnit = Unit::factory()->create();
    $otherLink = ApplicationLink::factory()->for($otherUnit)->create();
    Application::factory()->for($otherLink, 'applicationLink')->create();

    $this->withoutVite();

    $this->actingAs($landlord)
        ->get(route('applications.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('screening/applicants/All')
            ->has('applications.data', 2)
            ->where('applications.data.0.id', $appB->id)
            ->where('applications.data.0.property_name', 'Birch Flats')
            ->where('applications.data.0.unit_label', 'Unit 7')
            ->where('applications.data.1.id', $appA->id)
            ->has('applications.links')
            ->has('applications.total'),
        );
});

test('the all-applications index renders the empty state when the landlord has no applications', function () {
    $landlord = User::factory()->landlord()->create();

    $this->withoutVite();

    $this->actingAs($landlord)
        ->get(route('applications.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('screening/applicants/All')
            ->has('applications.data', 0)
            ->where('applications.total', 0),
        );
});

test('the all-applications page renders each application with its unit and property', function () {
    $landlord = User::factory()->landlord()->create();

    $property = Property::factory()->for($landlord, 'landlord')->create(['name' => 'Cedar House']);
    $unit = Unit::factory()->for($property)->create(['label' => 'Suite 3']);
    $link = ApplicationLink::factory()->for($unit)->create();
    $application = Application::factory()->for($link, 'applicationLink')->create([
        'applicant_first_name' => 'Rosa',
        'applicant_last_name' => 'Lin',
        'applicant_email' => 'rosa@example.com',
        'status' => ApplicationStatus::Reviewing,
        'submitted_at' => now(),
    ]);

    $this->withoutVite();

    $this->actingAs($landlord)
        ->get(route('applications.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('screening/applicants/All')
            ->where('applications.data.0.applicant_name', 'Rosa Lin')
            ->where('applications.data.0.applicant_email', 'rosa@example.com')
            ->where('applications.data.0.property_name', 'Cedar House')
            ->where('applications.data.0.unit_label', 'Suite 3')
            ->where('applications.data.0.status', ApplicationStatus::Reviewing->value)
            ->where('applications.data.0.url', route('applicants.show', $application)),
        );
});

test('the all-applications index filters by status', function () {
    $landlord = User::factory()->landlord()->create();
    $unit = applicantUnitOwnedBy($landlord);
    $link = ApplicationLink::factory()->for($unit)->create();

    $approved = Application::factory()->for($link, 'applicationLink')->create([
        'status' => ApplicationStatus::Approved,
    ]);
    Application::factory()->for($link, 'applicationLink')->create([
        'status' => ApplicationStatus::New,
    ]);

    $this->withoutVite();

    $this->actingAs($landlord)
        ->get(route('applications.index', ['status' => ApplicationStatus::Approved->value]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('applications.data', 1)
            ->where('applications.data.0.id', $approved->id)
            ->where('filters.status', ApplicationStatus::Approved->value),
        );
});

test('the all-applications index filters by property', function () {
    $landlord = User::factory()->landlord()->create();

    $propertyA = Property::factory()->for($landlord, 'landlord')->create();
    $unitA = Unit::factory()->for($propertyA)->create();
    $linkA = ApplicationLink::factory()->for($unitA)->create();
    $appA = Application::factory()->for($linkA, 'applicationLink')->create();

    $propertyB = Property::factory()->for($landlord, 'landlord')->create();
    $unitB = Unit::factory()->for($propertyB)->create();
    $linkB = ApplicationLink::factory()->for($unitB)->create();
    Application::factory()->for($linkB, 'applicationLink')->create();

    $this->withoutVite();

    $this->actingAs($landlord)
        ->get(route('applications.index', ['property' => $propertyA->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('applications.data', 1)
            ->where('applications.data.0.id', $appA->id)
            ->where('filters.property', $propertyA->id),
        );
});

test('the all-applications index searches by applicant name or email', function () {
    $landlord = User::factory()->landlord()->create();
    $unit = applicantUnitOwnedBy($landlord);
    $link = ApplicationLink::factory()->for($unit)->create();

    $match = Application::factory()->for($link, 'applicationLink')->create([
        'applicant_first_name' => 'Geraldine',
        'applicant_last_name' => 'Okafor',
        'applicant_email' => 'geraldine@example.com',
    ]);
    Application::factory()->for($link, 'applicationLink')->create([
        'applicant_first_name' => 'Tomas',
        'applicant_last_name' => 'Vega',
        'applicant_email' => 'tomas@example.com',
    ]);

    $this->withoutVite();

    $this->actingAs($landlord)
        ->get(route('applications.index', ['search' => 'geraldine']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('applications.data', 1)
            ->where('applications.data.0.id', $match->id)
            ->where('filters.search', 'geraldine'),
        );
});

test('the all-applications index exposes filter options', function () {
    $landlord = User::factory()->landlord()->create();
    $property = Property::factory()->for($landlord, 'landlord')->create(['name' => 'Maple Court']);
    Unit::factory()->for($property)->create();

    $this->withoutVite();

    $this->actingAs($landlord)
        ->get(route('applications.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('properties.0.name', 'Maple Court')
            ->has('statuses', 4),
        );
});

test('the all-applications index is paginated', function () {
    $landlord = User::factory()->landlord()->create();
    $unit = applicantUnitOwnedBy($landlord);
    $link = ApplicationLink::factory()->for($unit)->create();
    Application::factory()->count(25)->for($link, 'applicationLink')->create();

    $this->withoutVite();

    $this->actingAs($landlord)
        ->get(route('applications.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('applications.data', 20)
            ->where('applications.total', 25)
            ->where('applications.per_page', 20),
        );
});

test('the all-applications index exposes pagination links and a reachable second page', function () {
    $landlord = User::factory()->landlord()->create();
    $unit = applicantUnitOwnedBy($landlord);
    $link = ApplicationLink::factory()->for($unit)->create();
    Application::factory()->count(25)->for($link, 'applicationLink')->create();

    $this->withoutVite();

    $this->actingAs($landlord)
        ->get(route('applications.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('applications.links')
            ->where('applications.from', 1)
            ->where('applications.to', 20),
        );

    $this->actingAs($landlord)
        ->get(route('applications.index', ['page' => 2]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('applications.data', 5)
            ->where('applications.from', 21)
            ->where('applications.to', 25),
        );
});

test('the applications export streams a landlord-scoped csv with a header row', function () {
    $landlord = User::factory()->landlord()->create();

    $property = Property::factory()->for($landlord, 'landlord')->create(['name' => 'Maple Court']);
    $unit = Unit::factory()->for($property)->create(['label' => 'Unit 1']);
    $link = ApplicationLink::factory()->for($unit)->create();
    Application::factory()->for($link, 'applicationLink')->create([
        'applicant_first_name' => 'Rosa',
        'applicant_last_name' => 'Lin',
        'applicant_email' => 'rosa@example.com',
        'status' => ApplicationStatus::Reviewing,
    ]);

    $otherUnit = Unit::factory()->create();
    $otherLink = ApplicationLink::factory()->for($otherUnit)->create();
    Application::factory()->for($otherLink, 'applicationLink')->create([
        'applicant_first_name' => 'Hidden',
        'applicant_email' => 'hidden@example.com',
    ]);

    $response = $this->actingAs($landlord)->get(route('applications.export'));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $csv = $response->streamedContent();
    $rows = array_map('str_getcsv', array_values(array_filter(explode("\n", $csv), fn ($line) => $line !== '')));

    expect($rows[0])->toBe(['Applicant name', 'Email', 'Property', 'Unit', 'Status', 'Submitted at']);
    expect(array_slice($rows[1], 0, 5))->toBe(['Rosa Lin', 'rosa@example.com', 'Maple Court', 'Unit 1', 'Reviewing']);
    expect($rows[1][5])->not->toBe('');
    expect($csv)->not->toContain('hidden@example.com');
});

test('the applications export respects the active status filter', function () {
    $landlord = User::factory()->landlord()->create();
    $unit = applicantUnitOwnedBy($landlord);
    $link = ApplicationLink::factory()->for($unit)->create();

    Application::factory()->for($link, 'applicationLink')->create([
        'applicant_email' => 'approved@example.com',
        'status' => ApplicationStatus::Approved,
    ]);
    Application::factory()->for($link, 'applicationLink')->create([
        'applicant_email' => 'new@example.com',
        'status' => ApplicationStatus::New,
    ]);

    $response = $this->actingAs($landlord)
        ->get(route('applications.export', ['status' => ApplicationStatus::Approved->value]));

    $csv = $response->streamedContent();

    expect($csv)->toContain('approved@example.com')
        ->not->toContain('new@example.com');
});

test('the property applicants view lists applications across all of its units', function () {
    $landlord = User::factory()->landlord()->create();

    $property = Property::factory()->for($landlord, 'landlord')->create(['name' => 'Maple Court']);
    $unitA = Unit::factory()->for($property)->create(['label' => 'Unit 1']);
    $unitB = Unit::factory()->for($property)->create(['label' => 'Unit 2']);

    $linkA = ApplicationLink::factory()->for($unitA)->create();
    $older = Application::factory()->for($linkA, 'applicationLink')->create([
        'submitted_at' => now()->subDay(),
    ]);

    $linkB = ApplicationLink::factory()->for($unitB)->create();
    $newer = Application::factory()->for($linkB, 'applicationLink')->create([
        'submitted_at' => now(),
    ]);

    // Another property of the same landlord — must be excluded.
    $otherProperty = Property::factory()->for($landlord, 'landlord')->create();
    $otherUnit = Unit::factory()->for($otherProperty)->create();
    $otherLink = ApplicationLink::factory()->for($otherUnit)->create();
    Application::factory()->for($otherLink, 'applicationLink')->create();

    $this->withoutVite();

    $this->actingAs($landlord)
        ->get(route('properties.applicants.index', $property))
        ->assertInertia(fn (Assert $page) => $page
            ->component('screening/applicants/Property')
            ->where('property.name', 'Maple Court')
            ->has('applications.data', 2)
            ->where('applications.data.0.id', $newer->id)
            ->where('applications.data.0.unit_label', 'Unit 2')
            ->where('applications.data.1.id', $older->id)
            ->where('applications.data.1.unit_label', 'Unit 1')
            ->has('applications.links')
            ->where('applications.per_page', 20),
        );
});

test('a non-owner cannot view another landlords property applicants', function () {
    $landlord = User::factory()->landlord()->create();
    $property = Property::factory()->create();

    $this->actingAs($landlord)
        ->get(route('properties.applicants.index', $property))
        ->assertForbidden();
});

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
            ->has('applications.data', 2)
            ->where('applications.data.0.id', $newer->id)
            ->where('applications.data.0.applicant_name', 'Nadia '.$newer->applicant_last_name)
            ->where('applications.data.0.url', route('applicants.show', $newer))
            ->where('applications.data.1.id', $older->id),
        );
});

test('the per-unit applicants list is paginated', function () {
    $landlord = User::factory()->landlord()->create();
    $unit = applicantUnitOwnedBy($landlord);
    $link = ApplicationLink::factory()->for($unit)->create();
    Application::factory()->count(25)->for($link, 'applicationLink')->create();

    $this->withoutVite();

    $this->actingAs($landlord)
        ->get(route('units.applicants.index', $unit))
        ->assertInertia(fn (Assert $page) => $page
            ->has('applications.data', 20)
            ->where('applications.total', 25)
            ->where('applications.per_page', 20),
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
            ->where('applications.data.0.documents_count', 2),
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
        ->assertInertia(fn (Assert $page) => $page->has('applications.data', 0));
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

test('the detail page exposes the source link label and submitted timestamp', function () {
    $landlord = User::factory()->landlord()->create();
    $unit = applicantUnitOwnedBy($landlord);
    $link = ApplicationLink::factory()->for($unit)->create(['label' => 'Kijiji listing']);

    $application = Application::factory()->for($link, 'applicationLink')->create([
        'submitted_at' => now()->subDay(),
    ]);

    $this->withoutVite();

    $this->actingAs($landlord)
        ->get(route('applicants.show', $application))
        ->assertInertia(fn (Assert $page) => $page
            ->component('screening/applicants/Show')
            ->where('source', 'Kijiji listing')
            ->where('application.submitted_at', $application->submitted_at->toJSON())
            ->where('application.status_changed_at', null),
        );
});

test('changing an applications status stamps status_changed_at', function () {
    $landlord = User::factory()->landlord()->create();
    $unit = applicantUnitOwnedBy($landlord);
    $link = ApplicationLink::factory()->for($unit)->create();

    $application = Application::factory()->for($link, 'applicationLink')->create([
        'status' => ApplicationStatus::New,
        'status_changed_at' => null,
    ]);

    $this->actingAs($landlord)
        ->put(route('applicants.update', $application), [
            'status' => ApplicationStatus::Approved->value,
            'landlord_notes' => null,
        ]);

    expect($application->refresh()->status_changed_at)->not->toBeNull();

    // Editing only the notes must not re-stamp the status timeline.
    $stampedAt = $application->status_changed_at;

    $this->actingAs($landlord)
        ->put(route('applicants.update', $application), [
            'status' => ApplicationStatus::Approved->value,
            'landlord_notes' => 'Looks good.',
        ]);

    expect($application->refresh()->status_changed_at->equalTo($stampedAt))->toBeTrue();
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
