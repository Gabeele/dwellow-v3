<?php

use App\Models\Application;
use App\Models\ApplicationDraft;
use App\Models\ApplicationDraftDocument;
use App\Models\ApplicationLink;
use App\Models\Property;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    // The resume flow is cookie-driven; withCredentials lets JSON test requests
    // carry the per-link draft cookie (the framework encrypts it for us, matching
    // EncryptCookies on the way in).
    $this->withCredentials();
});

/**
 * An open application link on a fresh unit (auto-provisions the default form).
 */
function draftLink(): ApplicationLink
{
    $unit = Unit::factory()->for(Property::factory())->create();

    return ApplicationLink::factory()->for($unit)->create();
}

/**
 * A draft on the given link, with its resume cookie name for convenience.
 *
 * @return array{0: ApplicationDraft, 1: string}
 */
function seedDraft(ApplicationLink $link, array $answers = [], int $step = 0): array
{
    $draft = ApplicationDraft::factory()->for($link, 'applicationLink')
        ->create(['answers' => $answers, 'current_step' => $step]);

    return [$draft, ApplicationDraft::cookieName($link)];
}

/**
 * A complete set of valid answers for the default form, including both required
 * file uploads — the baseline a submission needs to be accepted.
 *
 * @return array<string, mixed>
 */
function draftValidAnswers(): array
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

test('autosave persists answers and step and sets the resume cookie', function () {
    $link = draftLink();

    $response = $this->putJson(route('screening.draft.save', $link->token), [
        'answers' => ['first_name' => 'Dana', 'last_name' => 'Tenant'],
        'current_step' => 2,
    ]);

    $response->assertNoContent();

    $draft = ApplicationDraft::query()->sole();

    expect($draft->application_link_id)->toBe($link->id)
        ->and($draft->answers['first_name'])->toBe('Dana')
        ->and($draft->current_step)->toBe(2);

    // The applicant's browser is handed the token so it can resume later.
    $response->assertCookie(ApplicationDraft::cookieName($link), $draft->token);
});

test('a second autosave with the cookie updates the same draft instead of creating another', function () {
    $link = draftLink();
    [$draft, $cookieName] = seedDraft($link, ['first_name' => 'Old']);

    $this->withCookie($cookieName, $draft->token)
        ->putJson(route('screening.draft.save', $link->token), [
            'answers' => ['first_name' => 'New'],
            'current_step' => 3,
        ])
        ->assertNoContent();

    expect(ApplicationDraft::query()->count())->toBe(1)
        ->and($draft->fresh()->answers['first_name'])->toBe('New')
        ->and($draft->fresh()->current_step)->toBe(3);
});

test('autosave stores only known non-file answers', function () {
    $link = draftLink();

    $this->putJson(route('screening.draft.save', $link->token), [
        'answers' => [
            'first_name' => 'Dana',
            'photo_id' => 'sneaky-filename.pdf', // a file field — files never live in answers
            'not_a_real_field' => 'junk',
        ],
        'current_step' => 0,
    ])->assertNoContent();

    $answers = ApplicationDraft::query()->sole()->answers;

    expect($answers)->toHaveKey('first_name')
        ->and($answers)->not->toHaveKey('photo_id')
        ->and($answers)->not->toHaveKey('not_a_real_field');
});

test('show rehydrates a draft answers, step, and files when the cookie is present', function () {
    Storage::fake('local');

    $link = draftLink();
    [$draft, $cookieName] = seedDraft($link, ['first_name' => 'Dana'], 1);
    ApplicationDraftDocument::factory()->create([
        'application_draft_id' => $draft->id,
        'field_key' => 'photo_id',
        'original_name' => 'id.pdf',
        'size' => 120,
    ]);

    $this->withCookie($cookieName, $draft->token)
        ->get(route('screening.show', $link->token))
        ->assertInertia(fn ($page) => $page
            ->component('screening/Apply')
            ->where('draft.answers.first_name', 'Dana')
            ->where('draft.current_step', 1)
            ->where('draft.files.0.field_key', 'photo_id')
            ->where('draft.files.0.original_name', 'id.pdf'),
        );
});

test('show returns no draft when there is no cookie', function () {
    $link = draftLink();

    $this->get(route('screening.show', $link->token))
        ->assertInertia(fn ($page) => $page
            ->component('screening/Apply')
            ->where('draft', null),
        );
});

test('show does not rehydrate a draft on a closed link', function () {
    $unit = Unit::factory()->for(Property::factory())->create();
    $link = ApplicationLink::factory()->for($unit)->revoked()->create();
    [$draft, $cookieName] = seedDraft($link, ['first_name' => 'Dana'], 1);

    $this->withCookie($cookieName, $draft->token)
        ->get(route('screening.show', $link->token))
        ->assertInertia(fn ($page) => $page->where('draft', null));
});

test('uploading a file saves it to the draft and re-uploading replaces it', function () {
    Storage::fake('local');

    $link = draftLink();
    [$draft, $cookieName] = seedDraft($link);

    $this->withCookie($cookieName, $draft->token)
        ->post(route('screening.draft.file.store', [$link->token, 'photo_id']), [
            'file' => UploadedFile::fake()->create('id.pdf', 120, 'application/pdf'),
        ])->assertOk()->assertJson(['original_name' => 'id.pdf']);

    $original = $draft->documents()->sole();
    Storage::disk('local')->assertExists($original->path);

    // Picking a new file for the same field replaces the old document and blob.
    $this->withCookie($cookieName, $draft->token)
        ->post(route('screening.draft.file.store', [$link->token, 'photo_id']), [
            'file' => UploadedFile::fake()->create('id-v2.pdf', 130, 'application/pdf'),
        ])->assertOk();

    expect($draft->documents()->count())->toBe(1)
        ->and($draft->documents()->sole()->original_name)->toBe('id-v2.pdf');
    Storage::disk('local')->assertMissing($original->path);
});

test('uploading to a field that is not a file field is rejected', function () {
    $link = draftLink();

    $this->post(route('screening.draft.file.store', [$link->token, 'first_name']), [
        'file' => UploadedFile::fake()->create('id.pdf', 120, 'application/pdf'),
    ])->assertNotFound();
});

test('removing a draft file deletes the document and its stored blob', function () {
    Storage::fake('local');

    $link = draftLink();
    [$draft, $cookieName] = seedDraft($link);

    $this->withCookie($cookieName, $draft->token)
        ->post(route('screening.draft.file.store', [$link->token, 'photo_id']), [
            'file' => UploadedFile::fake()->create('id.pdf', 120, 'application/pdf'),
        ])->assertOk();

    $path = $draft->documents()->sole()->path;

    $this->withCookie($cookieName, $draft->token)
        ->delete(route('screening.draft.file.destroy', [$link->token, 'photo_id']))
        ->assertNoContent();

    expect($draft->documents()->count())->toBe(0);
    Storage::disk('local')->assertMissing($path);
});

test('submitting migrates a draft file into the application and clears the draft and cookie', function () {
    Storage::fake('local');

    $link = draftLink();
    [$draft, $cookieName] = seedDraft($link);

    // The applicant uploaded their ID in an earlier session...
    $this->withCookie($cookieName, $draft->token)
        ->post(route('screening.draft.file.store', [$link->token, 'photo_id']), [
            'file' => UploadedFile::fake()->create('id.pdf', 120, 'application/pdf'),
        ])->assertOk();

    // ...then returns and submits without re-picking that file.
    $answers = draftValidAnswers();
    unset($answers['photo_id']);

    $response = $this->withCookie($cookieName, $draft->token)
        ->post(route('screening.store', $link->token), ['answers' => $answers]);

    $response->assertRedirect(route('screening.submitted', $link->token));

    $application = Application::query()->sole();

    // Both files are present: the migrated draft file and the inline one.
    expect($application->documents)->toHaveCount(2)
        ->and($application->answers['photo_id'])->toBe('id.pdf');

    $photoId = $application->documents->firstWhere('field_key', 'photo_id');
    expect($photoId->original_name)->toBe('id.pdf');
    Storage::disk('local')->assertExists($photoId->path);

    // The draft has done its job and is cleared, cookie included.
    expect(ApplicationDraft::query()->count())->toBe(0);
    $response->assertCookieExpired($cookieName);
});

test('a required file with no inline upload and no draft file is still rejected', function () {
    Storage::fake('local');

    $link = draftLink();

    $answers = draftValidAnswers();
    unset($answers['photo_id']);

    $this->post(route('screening.store', $link->token), ['answers' => $answers])
        ->assertSessionHasErrors('answers.photo_id');

    expect(Application::query()->count())->toBe(0);
});

test('pruning removes stale drafts and their stored files', function () {
    Storage::fake('local');

    $draft = ApplicationDraft::factory()->create();
    $document = ApplicationDraftDocument::factory()->create([
        'application_draft_id' => $draft->id,
        'disk' => 'local',
        'path' => 'application-drafts/'.$draft->id.'/id.pdf',
    ]);
    Storage::disk('local')->put($document->path, 'fake-bytes');

    // Age the draft past the retention window without bumping timestamps.
    ApplicationDraft::query()->whereKey($draft->id)->update(['updated_at' => now()->subDays(31)]);

    $this->artisan('model:prune', ['--model' => [ApplicationDraft::class]]);

    expect(ApplicationDraft::query()->count())->toBe(0);
    Storage::disk('local')->assertMissing($document->path);
});

test('pruning keeps recently-touched drafts', function () {
    $draft = ApplicationDraft::factory()->create();

    $this->artisan('model:prune', ['--model' => [ApplicationDraft::class]]);

    expect($draft->fresh())->not->toBeNull();
});
