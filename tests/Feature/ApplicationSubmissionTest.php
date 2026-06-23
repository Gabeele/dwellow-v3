<?php

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\ApplicationLink;
use App\Models\Document;
use App\Models\Property;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

/**
 * An open application link on a fresh unit (which auto-provisions the default form).
 */
function openLink(): ApplicationLink
{
    $unit = Unit::factory()->for(Property::factory())->create();

    return ApplicationLink::factory()->for($unit)->create();
}

/**
 * A full set of valid answers for the default form, with the two required files.
 *
 * @return array<string, mixed>
 */
function validSubmission(): array
{
    return [
        'first_name' => 'Dana',
        'last_name' => 'Tenant',
        'email' => 'dana@example.com',
        'phone' => '555-0100',
        'date_of_birth' => '1990-04-12',
        'current_address' => '1 Old Street, Toronto',
        'employer_name' => 'Acme Corp',
        'gross_monthly_income' => '4500',
        'desired_move_in_date' => '2026-08-01',
        'number_of_occupants' => '2',
        'screening_consent' => '1',
        'photo_id' => UploadedFile::fake()->create('id.pdf', 120, 'application/pdf'),
        'pay_stubs' => UploadedFile::fake()->create('stubs.pdf', 200, 'application/pdf'),
    ];
}

test('a valid submission creates an application with a snapshot, answers, and stored documents', function () {
    Storage::fake('local');

    $link = openLink();

    $response = $this->post(route('screening.store', $link->token), [
        'answers' => validSubmission(),
    ]);

    $response->assertRedirect(route('screening.submitted', $link->token));

    $application = Application::query()->sole();

    expect($application->application_link_id)->toBe($link->id);
    expect($application->unit_id)->toBe($link->unit_id);
    expect($application->status)->toBe(ApplicationStatus::New);
    expect($application->submitted_at)->not->toBeNull();
    expect($application->applicant_first_name)->toBe('Dana');
    expect($application->applicant_email)->toBe('dana@example.com');

    // The schema is snapshotted at submission time.
    expect($application->form_snapshot)->toBe($link->unit->applicationForm->fields);

    // Answers persist; file fields record the filename, not the binary.
    expect($application->answers['current_address'])->toBe('1 Old Street, Toronto');
    expect($application->answers['photo_id'])->toBe('id.pdf');

    // Each uploaded file becomes a Document on the private disk.
    expect($application->documents)->toHaveCount(2);

    $photoId = $application->documents->firstWhere('field_key', 'photo_id');
    expect($photoId)->not->toBeNull();
    expect($photoId->original_name)->toBe('id.pdf');
    expect($photoId->disk)->toBe('local');
    Storage::disk('local')->assertExists($photoId->path);
});

test('the submitted page renders a confirmation', function () {
    $link = openLink();

    $this->get(route('screening.submitted', $link->token))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('screening/Submitted')
            ->has('unit.label'),
        );
});

test('a submission missing a required field is rejected', function () {
    Storage::fake('local');

    $link = openLink();

    $answers = validSubmission();
    unset($answers['gross_monthly_income']);

    $this->post(route('screening.store', $link->token), ['answers' => $answers])
        ->assertSessionHasErrors('answers.gross_monthly_income');

    expect(Application::query()->count())->toBe(0);
    expect(Document::query()->count())->toBe(0);
});

test('a submission to a closed link is rejected', function () {
    Storage::fake('local');

    $unit = Unit::factory()->for(Property::factory())->create();
    $link = ApplicationLink::factory()->for($unit)->revoked()->create();

    $this->post(route('screening.store', $link->token), ['answers' => validSubmission()])
        ->assertForbidden();

    expect(Application::query()->count())->toBe(0);
});
