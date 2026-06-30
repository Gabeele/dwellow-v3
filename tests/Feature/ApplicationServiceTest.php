<?php

use App\Enums\ApplicationStatus;
use App\Jobs\ScoreApplication;
use App\Mail\ApplicationReceivedMail;
use App\Models\Application;
use App\Models\ApplicationDraft;
use App\Models\ApplicationDraftDocument;
use App\Models\ApplicationLink;
use App\Models\Property;
use App\Models\Unit;
use App\Models\User;
use App\Notifications\NewApplicationNotification;
use App\Screening\ApplicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

/**
 * An open application link on a fresh unit (which auto-provisions the default form).
 */
function serviceLink(?User $landlord = null): ApplicationLink
{
    $property = $landlord !== null
        ? Property::factory()->for($landlord, 'landlord')
        : Property::factory();

    $unit = Unit::factory()->for($property)->create();

    return ApplicationLink::factory()->for($unit)->create();
}

/**
 * A full set of valid answers for the default form, with the two required files.
 *
 * @return array<string, mixed>
 */
function serviceAnswers(): array
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

test('createApplication persists the application with a snapshot, answers, and stored documents', function () {
    Storage::fake('local');

    $link = serviceLink();

    $application = app(ApplicationService::class)->createApplication($link, serviceAnswers(), null);

    expect($application->exists)->toBeTrue();
    expect($application->application_link_id)->toBe($link->id);
    expect($application->unit_id)->toBe($link->unit_id);
    expect($application->status)->toBe(ApplicationStatus::New);
    expect($application->submitted_at)->not->toBeNull();
    expect($application->applicant_first_name)->toBe('Dana');
    expect($application->applicant_email)->toBe('dana@example.com');

    // The schema is snapshotted at creation time.
    expect($application->form_snapshot)->toEqual($link->unit->applicationForm->enabledFields());

    // Answers persist; file fields record the filename, not the binary.
    expect($application->answers['current_address'])->toBe('1 Old Street, Toronto');
    expect($application->answers['photo_id'])->toBe('id.pdf');

    // Each uploaded file becomes a Document on the private disk.
    expect($application->documents)->toHaveCount(2);

    $photoId = $application->documents->firstWhere('field_key', 'photo_id');
    expect($photoId)->not->toBeNull();
    expect($photoId->original_name)->toBe('id.pdf');
    Storage::disk('local')->assertExists($photoId->path);
});

test('createApplication migrates a draft file onto the application and clears the draft', function () {
    Storage::fake('local');

    $link = serviceLink();

    // A file the applicant uploaded in an earlier session lives on a draft.
    $draft = ApplicationDraft::factory()->for($link, 'applicationLink')->create();
    $draftDoc = ApplicationDraftDocument::factory()->for($draft, 'draft')->create([
        'field_key' => 'photo_id',
        'original_name' => 'old-id.pdf',
    ]);
    Storage::disk('local')->put($draftDoc->path, 'pdf-bytes');

    // This submission re-picks pay_stubs inline but relies on the draft for photo_id.
    $answers = serviceAnswers();
    unset($answers['photo_id']);

    $application = app(ApplicationService::class)->createApplication($link, $answers, $draft->token);

    // The migrated draft file becomes a Document, reusing the stored blob.
    $migrated = $application->documents->firstWhere('field_key', 'photo_id');
    expect($migrated)->not->toBeNull();
    expect($migrated->original_name)->toBe('old-id.pdf');
    expect($application->answers['photo_id'])->toBe('old-id.pdf');
    Storage::disk('local')->assertExists($migrated->path);
    Storage::disk('local')->assertMissing($draftDoc->path);

    // The spent draft is removed.
    expect(ApplicationDraft::query()->whereKey($draft->getKey())->exists())->toBeFalse();
});

test('createApplication emails the applicant and notifies the owning landlord once', function () {
    Storage::fake('local');
    Mail::fake();
    Notification::fake();

    $landlord = User::factory()->landlord()->create();
    $otherLandlord = User::factory()->landlord()->create();
    $link = serviceLink($landlord);

    app(ApplicationService::class)->createApplication($link, serviceAnswers(), null);

    Mail::assertQueued(ApplicationReceivedMail::class, fn (ApplicationReceivedMail $mail) => $mail->hasTo('dana@example.com'));
    Notification::assertSentToTimes($landlord, NewApplicationNotification::class, 1);
    Notification::assertNotSentTo($otherLandlord, NewApplicationNotification::class);
});

test('requestScore dispatches ScoreApplication for the application after commit', function () {
    Queue::fake();

    $application = Application::factory()->create();

    app(ApplicationService::class)->requestScore($application);

    Queue::assertPushed(
        ScoreApplication::class,
        fn (ScoreApplication $job) => $job->application->is($application),
    );
});
