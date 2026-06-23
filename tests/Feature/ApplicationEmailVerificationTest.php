<?php

use App\Models\Application;
use App\Models\ApplicationLink;
use App\Models\Property;
use App\Models\Unit;
use App\Notifications\ApplicationVerificationCodeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

/**
 * A valid set of answers for the default form, including the two required files.
 *
 * @return array<string, mixed>
 */
function verifiableAnswers(): array
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

test('requesting a code emails a one-time verification code', function () {
    Notification::fake();

    $unit = Unit::factory()->for(Property::factory())->create();
    $link = ApplicationLink::factory()->for($unit)->create();

    $this->postJson(route('screening.verify', $link->token), [
        'email' => 'dana@example.com',
    ])->assertOk();

    Notification::assertSentOnDemand(
        ApplicationVerificationCodeNotification::class,
        fn (ApplicationVerificationCodeNotification $notification): bool => $notification->code !== '',
    );
});

test('a closed link cannot issue a verification code', function () {
    Notification::fake();

    $unit = Unit::factory()->for(Property::factory())->create();
    $link = ApplicationLink::factory()->for($unit)->revoked()->create();

    $this->postJson(route('screening.verify', $link->token), [
        'email' => 'dana@example.com',
    ])->assertForbidden();

    Notification::assertNothingSent();
});

test('a submission without a valid code is blocked', function () {
    Storage::fake('local');

    $unit = Unit::factory()->for(Property::factory())->create();
    $link = ApplicationLink::factory()->for($unit)->create();

    $this->post(route('screening.store', $link->token), [
        'answers' => verifiableAnswers(),
        'verification_code' => '000000',
    ])->assertSessionHasErrors('verification_code');

    expect(Application::query()->count())->toBe(0);
});

test('a submission with a matching code succeeds', function () {
    Storage::fake('local');
    Notification::fake();

    $unit = Unit::factory()->for(Property::factory())->create();
    $link = ApplicationLink::factory()->for($unit)->create();

    $answers = verifiableAnswers();

    $this->postJson(route('screening.verify', $link->token), [
        'email' => $answers['email'],
    ])->assertOk();

    $code = '';
    Notification::assertSentOnDemand(
        ApplicationVerificationCodeNotification::class,
        function (ApplicationVerificationCodeNotification $notification) use (&$code): bool {
            $code = $notification->code;

            return true;
        },
    );

    $this->post(route('screening.store', $link->token), [
        'answers' => $answers,
        'verification_code' => $code,
    ])->assertRedirect(route('screening.submitted', $link->token));

    expect(Application::query()->count())->toBe(1);

    // The code is single-use: a replay with the same code is rejected.
    $this->post(route('screening.store', $link->token), [
        'answers' => verifiableAnswers(),
        'verification_code' => $code,
    ])->assertSessionHasErrors('verification_code');

    expect(Application::query()->count())->toBe(1);
});

test('the verification flow works through a whole-rental link', function () {
    Storage::fake('local');
    Notification::fake();

    // A whole rental auto-provisions its single backing unit (PropertyObserver),
    // so a link against that unit drives the same account-free verification flow.
    $property = Property::factory()->whole()->create();
    $unit = $property->units()->sole();
    $link = ApplicationLink::factory()->for($unit)->create();

    $answers = verifiableAnswers();

    $this->postJson(route('screening.verify', $link->token), [
        'email' => $answers['email'],
    ])->assertOk();

    $code = '';
    Notification::assertSentOnDemand(
        ApplicationVerificationCodeNotification::class,
        function (ApplicationVerificationCodeNotification $notification) use (&$code): bool {
            $code = $notification->code;

            return true;
        },
    );

    // A wrong code is rejected even on a valid whole-rental link.
    $this->post(route('screening.store', $link->token), [
        'answers' => $answers,
        'verification_code' => '000000',
    ])->assertSessionHasErrors('verification_code');

    expect(Application::query()->count())->toBe(0);

    // The mailed code allows submission and attributes it to the backing unit.
    $this->post(route('screening.store', $link->token), [
        'answers' => $answers,
        'verification_code' => $code,
    ])->assertRedirect(route('screening.submitted', $link->token));

    expect(Application::query()->count())->toBe(1);
    expect(Application::query()->sole()->unit_id)->toBe($unit->id);
});
