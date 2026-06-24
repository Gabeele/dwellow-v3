<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('the pricing page renders with plans and SEO', function () {
    $response = $this->get(route('pricing'))->assertOk();

    $response->assertInertia(fn (Assert $page) => $page
        ->component('marketing/Pricing')
        ->has('plans', 3)
        ->has('faq')
        ->where('plans.0.name', 'Starter')
        ->where('plans.1.highlighted', true)
        ->where('seo.title', 'Pricing — Dwellow')
        ->where('seo.url', route('pricing'))
    );

    expect($response->getContent())
        ->toContain('<title>Pricing — Dwellow</title>')
        ->toContain('"@type": "FAQPage"');
});

test('the docs page renders how-to guides', function () {
    $this->get(route('docs'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('marketing/Docs')
            ->has('guides', 4)
            ->has('faq')
            ->where('guides.0.id', 'add-property')
            ->has('guides.0.steps')
            ->has('guides.0.markers')
            ->where('seo.url', route('docs'))
        );
});

test('the roadmap page renders a grouped timeline', function () {
    $this->get(route('roadmap'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('marketing/Roadmap')
            ->has('groups', 4)
            ->has('faq')
            ->where('groups.0.status', 'shipped')
            ->where('groups.0.label', 'Shipped')
            ->has('groups.0.items')
            ->where('seo.url', route('roadmap'))
        );
});

test('marketing pages set a canonical url for each route', function () {
    foreach (['pricing', 'docs', 'roadmap'] as $name) {
        $html = $this->get(route($name))->assertOk()->getContent();

        expect($html)->toContain('<link rel="canonical" href="'.route($name).'">');
    }
});

test('authenticated users still land on the dashboard from home', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertRedirect(route('dashboard'));
});

test('marketing pages are reachable while authenticated', function () {
    $user = User::factory()->create();

    foreach (['pricing', 'docs', 'roadmap'] as $name) {
        $this->actingAs($user)->get(route($name))->assertOk();
    }
});
