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
            ->has('stats', 3)
            ->has('steps', 5)
            ->has('features', 3)
            ->has('comparison.columns', 3)
            ->has('comparison.rows')
            ->has('faq')
            ->has('roadmap', 3)
            ->where('roadmap.0.phase', 'Now')
            ->where('roadmap.0.title', 'Tenant screening')
            ->where('roadmap.0.current', true)
            ->where('features.0.title', 'Document-based, not bureau-based')
            ->where('comparison.columns.0', 'Dwellow')
        );
});

test('the landing page emits FAQ structured data for answer engines', function () {
    $html = $this->get(route('home'))->assertOk()->getContent();

    expect($html)
        ->toContain('"@type": "FAQPage"')
        ->toContain('"@type": "Question"');
});

test('the landing page renders SEO metadata in the response', function () {
    $response = $this->get(route('home'))->assertOk();

    $response->assertInertia(fn (Assert $page) => $page
        ->where('seo.title', 'Dwellow — Tenant screening for small landlords')
        ->where('seo.url', route('home'))
        ->where('seo.description', fn (string $description) => str_contains($description, 'Score'))
    );

    $html = $response->getContent();

    expect($html)
        ->toContain('<title>Dwellow — Tenant screening for small landlords</title>')
        ->toContain('<meta name="description"')
        ->toContain('<link rel="canonical" href="'.route('home').'">')
        ->toContain('property="og:title"')
        ->toContain('name="twitter:card"')
        ->toContain('application/ld+json')
        ->toContain('SoftwareApplication');
});

test('authenticated users are redirected to the dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertRedirect(route('dashboard'));
});
