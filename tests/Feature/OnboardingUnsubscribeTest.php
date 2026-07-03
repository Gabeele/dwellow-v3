<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('visiting the signed unsubscribe link stamps the user as opted out', function () {
    $landlord = User::factory()->landlord()->create();

    $this->get($landlord->onboardingUnsubscribeUrl())->assertRedirect(route('home'));

    expect($landlord->fresh()->hasUnsubscribedFromOnboarding())->toBeTrue();
});

test('visiting the link twice is idempotent', function () {
    $landlord = User::factory()->landlord()->create();

    $this->get($landlord->onboardingUnsubscribeUrl());
    $firstStampedAt = $landlord->fresh()->unsubscribed_from_onboarding_at;

    $this->get($landlord->onboardingUnsubscribeUrl())->assertRedirect(route('home'));

    expect($landlord->fresh()->unsubscribed_from_onboarding_at->eq($firstStampedAt))->toBeTrue();
});

test('an unsigned or tampered unsubscribe url is rejected', function () {
    $landlord = User::factory()->landlord()->create();

    $this->get(route('onboarding.unsubscribe', ['user' => $landlord]))->assertForbidden();

    expect($landlord->fresh()->hasUnsubscribedFromOnboarding())->toBeFalse();
});
