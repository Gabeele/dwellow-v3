<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('guests see the marketing landing page', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Welcome')
            ->has('steps', 5)
            ->has('features', 3)
            ->has('roadmap', 3)
            ->where('roadmap.0.phase', 'Now')
            ->where('roadmap.0.title', 'Tenant screening')
            ->where('roadmap.0.current', true)
            ->where('features.0.title', 'Document-based, not bureau-based')
        );
});

test('authenticated users are redirected to the dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertRedirect(route('dashboard'));
});
