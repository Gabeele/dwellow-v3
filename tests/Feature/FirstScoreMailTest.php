<?php

use App\Mail\FirstScoreMail;
use App\Models\Application;
use App\Models\ApplicationLink;
use App\Models\Property;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function applicationForLandlord(User $landlord): Application
{
    $property = Property::factory()->multiUnit()->for($landlord, 'landlord')->create();
    $unit = Unit::factory()->for($property)->create();
    $link = ApplicationLink::factory()->for($unit)->create();

    return Application::factory()->for($link, 'applicationLink')->create(['unit_id' => $unit->id]);
}

test('renders with the correct subject, key copy, logo, and unsubscribe link', function () {
    $landlord = User::factory()->landlord()->create(['name' => 'Jane Doe']);
    $application = applicationForLandlord($landlord);
    $application->applicant_first_name = 'Alex';
    $application->applicant_last_name = 'Rivera';
    $application->save();

    $mail = new FirstScoreMail($application);

    expect($mail->envelope()->subject)->toBe('Your first Score is in');

    $rendered = $mail->render();

    expect($rendered)
        ->toContain('Your first applicant is scored, Jane')
        ->toContain('Alex Rivera applied for')
        ->toContain($application->unit->label)
        ->toContain('Compare applicants')
        ->toContain(route('applications.index'))
        ->toContain('The Dwellow team')
        ->toContain('images/dwellow-email-logo.png')
        ->toContain('Unsubscribe')
        ->toContain($landlord->onboardingUnsubscribeUrl());
});

test('is queued', function () {
    $landlord = User::factory()->landlord()->create();
    $application = applicationForLandlord($landlord);

    expect(new FirstScoreMail($application))->toBeInstanceOf(ShouldQueue::class);
});
