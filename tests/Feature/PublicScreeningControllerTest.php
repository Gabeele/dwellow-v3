<?php

use App\Models\ApplicationLink;
use App\Models\Property;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

/**
 * Create an application link on a fresh unit, applying the given factory states.
 */
function screeningLink(string ...$states): ApplicationLink
{
    $unit = Unit::factory()->for(Property::factory())->create();

    $factory = ApplicationLink::factory()->for($unit);

    foreach ($states as $state) {
        $factory = $factory->{$state}();
    }

    return $factory->create();
}

test('an open link renders the apply page with the units sections', function () {
    $link = screeningLink();

    $this->get(route('screening.show', $link->token))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('screening/Apply')
            ->where('isOpen', true)
            ->has('unit.label')
            ->has('unit.address')
            ->has('sections', count($link->unit->applicationForm->enabledSections())),
        );
});

test('a disabled section is omitted from the public apply payload', function () {
    $link = screeningLink();

    $sections = $link->unit->applicationForm->sections;
    $disabledKey = collect($sections)->firstWhere('locked', false)['key'];
    $sections = array_map(function (array $section) use ($disabledKey): array {
        if ($section['key'] === $disabledKey) {
            $section['enabled'] = false;
        }

        return $section;
    }, $sections);
    $link->unit->applicationForm->update(['sections' => $sections]);

    $this->get(route('screening.show', $link->token))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('sections', count($sections) - 1)
            ->where('sections', fn ($rendered) => collect($rendered)
                ->pluck('key')
                ->doesntContain($disabledKey)),
        );
});

test('a section with no enabled key is treated as enabled', function () {
    $link = screeningLink();

    // Mimic a form saved before a flag existed by stripping `enabled` from each section.
    $sections = array_map(function (array $section): array {
        unset($section['enabled']);

        return $section;
    }, $link->unit->applicationForm->sections);
    $link->unit->applicationForm->update(['sections' => $sections]);

    $this->get(route('screening.show', $link->token))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('sections', count($sections)));
});

test('a revoked link renders the closed state', function () {
    $link = screeningLink('revoked');

    $this->get(route('screening.show', $link->token))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('screening/Apply')
            ->where('isOpen', false)
            ->where('sections', []),
        );
});

test('an expired link renders the closed state', function () {
    $link = screeningLink('expired');

    $this->get(route('screening.show', $link->token))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('isOpen', false)
            ->where('sections', []),
        );
});

test('a not-accepting link renders the closed state', function () {
    $link = screeningLink('notAccepting');

    $this->get(route('screening.show', $link->token))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('isOpen', false)
            ->where('sections', []),
        );
});

test('an unknown token 404s', function () {
    $this->get(route('screening.show', 'this-token-does-not-exist'))
        ->assertNotFound();
});
