<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Fortify\Features;

uses(RefreshDatabase::class);

test('login page renders the auth/Login component', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('auth/Login'));
});

test('register page renders the auth/Register component', function () {
    $this->skipUnlessFortifyHas(Features::registration());

    $this->get(route('register'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('auth/Register'));
});

test('forgot password page renders the auth/ForgotPassword component', function () {
    $this->skipUnlessFortifyHas(Features::resetPasswords());

    $this->get(route('password.request'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('auth/ForgotPassword'));
});

test('reset password page renders the auth/ResetPassword component', function () {
    $this->skipUnlessFortifyHas(Features::resetPasswords());

    $this->get(route('password.reset', 'test-token'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('auth/ResetPassword'));
});

test('verify email page renders the auth/VerifyEmail component for unverified users', function () {
    $this->skipUnlessFortifyHas(Features::emailVerification());

    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('verification.notice'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('auth/VerifyEmail'));
});

test('confirm password page renders the auth/ConfirmPassword component', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('password.confirm'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('auth/ConfirmPassword'));
});
