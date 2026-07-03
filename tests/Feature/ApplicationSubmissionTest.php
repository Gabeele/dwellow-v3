<?php

use App\Enums\ApplicationStatus;
use App\Jobs\ScoreApplication;
use App\Mail\ApplicationReceivedMail;
use App\Models\Application;
use App\Models\ApplicationLink;
use App\Models\Document;
use App\Models\Property;
use App\Models\Unit;
use App\Models\User;
use App\Notifications\NewApplicationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
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

    $answers = validSubmission();

    $response = $this->post(route('screening.store', $link->token), [
        'answers' => $answers,
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
    expect($application->form_snapshot)->toEqual($link->unit->applicationForm->enabledFields());

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

test('a valid submission emails the applicant a confirmation', function () {
    Storage::fake('local');
    Mail::fake();

    $link = openLink();

    $this->post(route('screening.store', $link->token), [
        'answers' => validSubmission(),
    ])->assertRedirect(route('screening.submitted', $link->token));

    Mail::assertQueued(ApplicationReceivedMail::class, function (ApplicationReceivedMail $mail) {
        return $mail->hasTo('dana@example.com');
    });
});

test('a valid submission notifies the owning landlord once', function () {
    Storage::fake('local');
    Notification::fake();

    $landlord = User::factory()->landlord()->create();
    $unit = Unit::factory()->for(Property::factory()->for($landlord, 'landlord'))->create();
    $link = ApplicationLink::factory()->for($unit)->create();

    $otherLandlord = User::factory()->landlord()->create();

    $this->post(route('screening.store', $link->token), [
        'answers' => validSubmission(),
    ])->assertRedirect(route('screening.submitted', $link->token));

    Notification::assertSentToTimes($landlord, NewApplicationNotification::class, 1);
    Notification::assertNotSentTo($otherLandlord, NewApplicationNotification::class);
});

test('a rejected submission does not notify the landlord', function () {
    Storage::fake('local');
    Notification::fake();

    $landlord = User::factory()->landlord()->create();
    $unit = Unit::factory()->for(Property::factory()->for($landlord, 'landlord'))->create();
    $link = ApplicationLink::factory()->for($unit)->create();

    $answers = validSubmission();
    unset($answers['gross_monthly_income']);

    $this->post(route('screening.store', $link->token), ['answers' => $answers])
        ->assertSessionHasErrors('answers.gross_monthly_income');

    Notification::assertNothingSent();
});

test('a rejected submission does not email the applicant', function () {
    Storage::fake('local');
    Mail::fake();

    $link = openLink();

    $answers = validSubmission();
    unset($answers['gross_monthly_income']);

    $this->post(route('screening.store', $link->token), ['answers' => $answers])
        ->assertSessionHasErrors('answers.gross_monthly_income');

    Mail::assertNothingOutgoing();
});

test('the submitted page renders a confirmation', function () {
    $link = openLink();

    $this->get(route('screening.submitted', $link->token))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('screening/Submitted')
            ->has('unit.label')
            ->has('unit.address.line1')
            ->has('unit.address.city')
            ->has('reference'),
        );
});

test('a created application is assigned a unique public reference id', function () {
    $first = Application::factory()->create();
    $second = Application::factory()->create();

    expect($first->public_id)->not->toBeEmpty()
        ->and($second->public_id)->not->toBeEmpty()
        ->and($first->public_id)->not->toBe($second->public_id);
});

test('the submitted page exposes the new application reference after a submission', function () {
    Storage::fake('local');

    $link = openLink();

    $this->followingRedirects()
        ->post(route('screening.store', $link->token), ['answers' => validSubmission()])
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('screening/Submitted')
            ->where('reference', Application::query()->sole()->public_id),
        );
});

test('the applicant confirmation email shows the application reference', function () {
    Storage::fake('local');

    $link = openLink();

    $this->post(route('screening.store', $link->token), ['answers' => validSubmission()]);

    $application = Application::query()->sole();

    $rendered = (new ApplicationReceivedMail($application))->render();

    expect($rendered)->toContain($application->public_id);
});

test('a submission omitting a disabled section succeeds and the snapshot reflects only active fields', function () {
    Storage::fake('local');

    $link = openLink();

    // Disable the whole employment & income section (which contains pay_stubs and
    // the required income fields) so none of its fields render or validate.
    $sections = array_map(function (array $section): array {
        if ($section['key'] === 'employment_income') {
            $section['enabled'] = false;
        }

        return $section;
    }, $link->unit->applicationForm->sections);
    $link->unit->applicationForm->update(['sections' => $sections]);

    $answers = validSubmission();
    unset($answers['pay_stubs'], $answers['employer_name'], $answers['gross_monthly_income']);

    $this->post(route('screening.store', $link->token), [
        'answers' => $answers,
    ])->assertRedirect(route('screening.submitted', $link->token));

    $application = Application::query()->sole();

    // The snapshot captures only the fields that were active at submit time.
    expect(collect($application->form_snapshot)->pluck('key'))
        ->not->toContain('pay_stubs')
        ->not->toContain('employer_name');

    // Only the still-enabled photo_id file is stored as a document.
    expect($application->documents)->toHaveCount(1);
    expect($application->documents->firstWhere('field_key', 'pay_stubs'))->toBeNull();
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

test('a submission with the honeypot decoy filled is silently discarded', function () {
    Storage::fake('local');

    $link = openLink();

    // A bot fills the hidden decoy field; the applicant sees the success page but
    // nothing is persisted. rendered_at is set well in the past so only the decoy
    // triggers the spam check.
    $this->post(route('screening.store', $link->token), [
        'answers' => validSubmission(),
        'contact_channel' => 'http://spam.example',
        'rendered_at' => time() - 60,
    ])->assertRedirect(route('screening.submitted', $link->token));

    expect(Application::query()->count())->toBe(0);
});

test('a submission returned faster than a human could fill it is silently discarded', function () {
    Storage::fake('local');

    $link = openLink();

    $this->post(route('screening.store', $link->token), [
        'answers' => validSubmission(),
        'rendered_at' => time(),
    ])->assertRedirect(route('screening.submitted', $link->token));

    expect(Application::query()->count())->toBe(0);
});

test('the public submission endpoint is rate limited per IP', function () {
    $link = openLink();

    // The throttle runs before validation, so even invalid posts count toward the
    // per-minute limit. The eleventh request in the window is blocked with a 429.
    foreach (range(1, 10) as $ignored) {
        $this->post(route('screening.store', $link->token), ['answers' => []]);
    }

    $this->post(route('screening.store', $link->token), ['answers' => []])
        ->assertStatus(429);
});

test('a valid submission queues the AI Score for the new application', function () {
    Storage::fake('local');
    Queue::fake();

    $link = openLink();

    $this->post(route('screening.store', $link->token), [
        'answers' => validSubmission(),
    ])->assertRedirect(route('screening.submitted', $link->token));

    $application = Application::query()->sole();

    Queue::assertPushed(
        ScoreApplication::class,
        fn (ScoreApplication $job) => $job->application->is($application),
    );
});

test('a rejected submission does not queue the AI Score', function () {
    Storage::fake('local');
    Queue::fake();

    $link = openLink();

    $answers = validSubmission();
    unset($answers['gross_monthly_income']);

    $this->post(route('screening.store', $link->token), ['answers' => $answers])
        ->assertSessionHasErrors('answers.gross_monthly_income');

    Queue::assertNotPushed(ScoreApplication::class);
});

test('a spam submission does not queue the AI Score', function () {
    Storage::fake('local');
    Queue::fake();

    $link = openLink();

    $this->post(route('screening.store', $link->token), [
        'answers' => validSubmission(),
        'contact_channel' => 'http://spam.example',
        'rendered_at' => time() - 60,
    ])->assertRedirect(route('screening.submitted', $link->token));

    Queue::assertNotPushed(ScoreApplication::class);
});

test('a submission to a closed link is sent to the friendly closed page', function () {
    Storage::fake('local');

    $unit = Unit::factory()->for(Property::factory())->create();
    $link = ApplicationLink::factory()->for($unit)->revoked()->create();

    // A link that closed after the page loaded redirects back to the apply page,
    // which renders the closed state — no bare 403.
    $this->post(route('screening.store', $link->token), [
        'answers' => validSubmission(),
    ])->assertRedirect(route('screening.show', $link->token));

    expect(Application::query()->count())->toBe(0);
});
