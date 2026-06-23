<?php

use App\Models\SentEmail;
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
