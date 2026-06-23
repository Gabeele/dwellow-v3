<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('new users can register with a chosen role from the form', function () {
    $this->post(route('register.store'), [
        'name' => 'New Landlord',
        'email' => 'landlord@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'roles' => ['landlord'],
    ]);

    $user = User::where('email', 'landlord@example.com')->firstOrFail();

    expect($user->isLandlord())->toBeTrue()
        ->and($user->isTenant())->toBeFalse();
});
