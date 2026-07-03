<?php

use App\Mail\CheckInMail;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('renders with the correct subject, key copy, logo, and unsubscribe link', function () {
    $landlord = User::factory()->landlord()->create(['name' => 'Jane Doe']);

    $mail = new CheckInMail($landlord);

    expect($mail->envelope()->subject)->toBe("How's screening going?");

    $rendered = $mail->render();

    expect($rendered)
        ->toContain('How\'s it going so far, Jane')
        ->toContain("one of Dwellow's early landlords")
        ->toContain('Just hit reply')
        ->toContain('Thanks for giving Dwellow a try')
        ->toContain('Gavin')
        ->toContain('images/dwellow-email-logo.png')
        ->toContain('Unsubscribe')
        ->toContain($landlord->onboardingUnsubscribeUrl());
});

test('is queued', function () {
    $landlord = User::factory()->landlord()->create();

    expect(new CheckInMail($landlord))->toBeInstanceOf(ShouldQueue::class);
});
