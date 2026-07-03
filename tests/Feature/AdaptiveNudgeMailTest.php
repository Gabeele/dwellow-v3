<?php

use App\Mail\AdaptiveNudgeMail;
use App\Models\ApplicationLink;
use App\Models\Property;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('renders with the correct subject, key copy, logo, and unsubscribe link', function () {
    $landlord = User::factory()->landlord()->create(['name' => 'Jane Doe']);

    $rendered = (new AdaptiveNudgeMail($landlord))->render();

    expect((new AdaptiveNudgeMail($landlord))->envelope()->subject)
        ->toBe('Your next step takes about five minutes');

    expect($rendered)
        ->toContain("You're one step from your first applicant, Jane")
        ->toContain('Here\'s the one thing standing between you and your first scored applicant')
        ->toContain('Picking a tenant is nerve-wracking')
        ->toContain('Just reply to this email')
        ->toContain('The Dwellow team')
        ->toContain('images/dwellow-email-logo.png')
        ->toContain('Unsubscribe')
        ->toContain($landlord->onboardingUnsubscribeUrl());
});

test('is queued', function () {
    $landlord = User::factory()->landlord()->create();

    expect(new AdaptiveNudgeMail($landlord))->toBeInstanceOf(ShouldQueue::class);
});

test('renders the add-property CTA when the landlord has no property', function () {
    $landlord = User::factory()->landlord()->create();

    $rendered = (new AdaptiveNudgeMail($landlord))->render();

    expect($rendered)
        ->toContain('Add your first property')
        ->toContain(route('properties.create'))
        ->toContain('Add a property and its units');
});

test('renders the build-application CTA when the landlord has a property but no active link', function () {
    $landlord = User::factory()->landlord()->create();
    $property = Property::factory()->multiUnit()->for($landlord, 'landlord')->create();
    $unit = Unit::factory()->for($property)->create();
    $unit->applicationLinks()->delete();

    $rendered = (new AdaptiveNudgeMail($landlord))->render();

    expect($rendered)
        ->toContain('Build your application')
        ->toContain(route('units.form.edit', $unit))
        ->toContain('Build the application for your unit');
});

test('renders the share-link CTA when the landlord has an active link but no applicant', function () {
    $landlord = User::factory()->landlord()->create();
    $property = Property::factory()->multiUnit()->for($landlord, 'landlord')->create();
    $unit = Unit::factory()->for($property)->create();
    $unit->applicationLinks()->delete();
    ApplicationLink::factory()->for($unit)->create();

    $rendered = (new AdaptiveNudgeMail($landlord))->render();

    expect($rendered)
        ->toContain('Copy your link')
        ->toContain(route('properties.show', $property))
        ->toContain('Your link is ready — share it in your listing');
});
