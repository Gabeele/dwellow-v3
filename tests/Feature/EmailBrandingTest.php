<?php

use App\Mail\WelcomeMail;
use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the welcome email is branded with the Dwellow logo and capitalisation', function () {
    $user = User::factory()->create(['name' => 'Jane']);

    $rendered = (new WelcomeMail($user))->render();

    expect($rendered)
        ->toContain('Welcome to Dwellow, Jane')
        ->toContain('images/dwellow-email-logo.png')
        ->not->toContain('laravel.com/img/notification-logo')
        ->not->toContain('Welcome to dwellow');
});

test('the verification email is branded with the Dwellow logo and capitalisation', function () {
    $user = User::factory()->unverified()->create();

    $mailMessage = (new VerifyEmailNotification)->toMail($user);

    expect($mailMessage->subject)->toBe('Verify your Dwellow email address');

    expect((string) $mailMessage->render())
        ->toContain('Welcome to Dwellow')
        ->toContain('images/dwellow-email-logo.png')
        ->not->toContain('laravel.com/img/notification-logo');
});
