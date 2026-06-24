<?php

use App\Listeners\RecordSentEmail;
use App\Models\SentEmail;
use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

test('sending an email records a SentEmail row', function () {
    Mail::raw('Hello there, welcome to dwellow.', function ($message) {
        $message->to('recipient@example.com')
            ->subject('Greetings');
    });

    expect(SentEmail::count())->toBe(1);

    $email = SentEmail::first();

    expect($email->subject)->toBe('Greetings')
        ->and($email->to)->toContain('recipient@example.com')
        ->and($email->body)->toContain('Hello there')
        ->and($email->sent_at)->not->toBeNull();
});

test('recipients are captured as an array', function () {
    Mail::raw('Body', function ($message) {
        $message->to(['a@example.com', 'b@example.com'])
            ->cc('c@example.com')
            ->subject('Multi');
    });

    $email = SentEmail::firstOrFail();

    expect($email->to)->toEqual(['a@example.com', 'b@example.com'])
        ->and($email->cc)->toEqual(['c@example.com']);
});

test('password reset email body is redacted but still audited', function () {
    $user = User::factory()->create();

    $user->notify(new ResetPassword('secret-reset-token'));

    $email = SentEmail::firstOrFail();

    // The audit trail is preserved...
    expect($email->to)->toContain($user->email)
        ->and($email->sent_at)->not->toBeNull()
        // ...but the single-use link is never persisted.
        ->and($email->body)->toBe(RecordSentEmail::REDACTED_BODY)
        ->and($email->body)->not->toContain('secret-reset-token');
});

test('email verification body is redacted, including the branded override', function () {
    $user = User::factory()->unverified()->create();

    $user->notify(new VerifyEmailNotification);

    $email = SentEmail::firstOrFail();

    expect($email->to)->toContain($user->email)
        ->and($email->body)->toBe(RecordSentEmail::REDACTED_BODY);
});
