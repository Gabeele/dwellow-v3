<?php

use App\Mail\AdaptiveNudgeMail;
use App\Mail\CheckInMail;
use App\Models\Application;
use App\Models\ApplicationLink;
use App\Models\Property;
use App\Models\Score;
use App\Models\SentEmail;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

test('sends the adaptive nudge at day 2 to a landlord who has not activated', function () {
    Mail::fake();

    $landlord = User::factory()->landlord()->create(['created_at' => now()->subDays(2)]);

    Artisan::call('dwellow:send-onboarding-emails');

    Mail::assertQueued(AdaptiveNudgeMail::class, fn (AdaptiveNudgeMail $mail) => $mail->hasTo($landlord->email));
    Mail::assertNotQueued(CheckInMail::class);
});

test('does not send the adaptive nudge before day 2', function () {
    Mail::fake();

    User::factory()->landlord()->create(['created_at' => now()->subDay()]);

    Artisan::call('dwellow:send-onboarding-emails');

    Mail::assertNothingQueued();
});

test('sends the check-in at day 10 to a landlord who has not activated', function () {
    Mail::fake();

    $landlord = User::factory()->landlord()->create(['created_at' => now()->subDays(10)]);

    Artisan::call('dwellow:send-onboarding-emails');

    Mail::assertQueued(AdaptiveNudgeMail::class, fn (AdaptiveNudgeMail $mail) => $mail->hasTo($landlord->email));
    Mail::assertQueued(CheckInMail::class, fn (CheckInMail $mail) => $mail->hasTo($landlord->email));
});

test('is idempotent: running the command twice sends each email once', function () {
    Mail::fake();

    $landlord = User::factory()->landlord()->create(['created_at' => now()->subDays(10)]);

    Artisan::call('dwellow:send-onboarding-emails');
    Artisan::call('dwellow:send-onboarding-emails');

    Mail::assertQueued(AdaptiveNudgeMail::class, 1);
    Mail::assertQueued(CheckInMail::class, 1);

    expect(SentEmail::where('subject', AdaptiveNudgeMail::SUBJECT)->count())->toBe(1)
        ->and(SentEmail::where('subject', CheckInMail::SUBJECT)->count())->toBe(1);
});

test('skips a landlord who has opted out of onboarding emails', function () {
    Mail::fake();

    User::factory()->landlord()->create([
        'created_at' => now()->subDays(10),
        'unsubscribed_from_onboarding_at' => now(),
    ]);

    Artisan::call('dwellow:send-onboarding-emails');

    Mail::assertNothingQueued();
});

test('skips a landlord who has already scored an applicant', function () {
    Mail::fake();

    $landlord = User::factory()->landlord()->create(['created_at' => now()->subDays(10)]);
    $property = Property::factory()->multiUnit()->for($landlord, 'landlord')->create();
    $unit = Unit::factory()->for($property)->create();
    $link = ApplicationLink::factory()->for($unit)->create();
    $application = Application::factory()->for($link, 'applicationLink')->create(['unit_id' => $unit->id]);
    Score::factory()->for($application)->create();

    Artisan::call('dwellow:send-onboarding-emails');

    Mail::assertNothingQueued();
});
