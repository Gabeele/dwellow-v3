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

test('an open link renders the apply page with the units fields', function () {
    $link = screeningLink();

    $this->get(route('screening.show', $link->token))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('screening/Apply')
            ->where('isOpen', true)
            ->has('unit.label')
            ->has('unit.address')
            ->has('fields', count($link->unit->applicationForm->fields)),
        );
});

test('a disabled field is omitted from the public apply payload', function () {
    $link = screeningLink();

    $fields = $link->unit->applicationForm->fields;
    $disabledKey = $fields[0]['key'];
    $fields[0]['enabled'] = false;
    $link->unit->applicationForm->update(['fields' => $fields]);

    $this->get(route('screening.show', $link->token))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('fields', count($fields) - 1)
            ->where('fields', fn ($rendered) => collect($rendered)
                ->pluck('key')
                ->doesntContain($disabledKey)),
        );
});

test('a form saved before the enabled toggle still renders every field', function () {
    $link = screeningLink();

    // Mimic a legacy form by stripping the `enabled` key from each field.
    $fields = array_map(function (array $field): array {
        unset($field['enabled']);

        return $field;
    }, $link->unit->applicationForm->fields);
    $link->unit->applicationForm->update(['fields' => $fields]);

    $this->get(route('screening.show', $link->token))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('fields', count($fields)));
});

test('a revoked link renders the closed state', function () {
    $link = screeningLink('revoked');

    $this->get(route('screening.show', $link->token))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('screening/Apply')
            ->where('isOpen', false)
            ->where('fields', []),
        );
});

test('an expired link renders the closed state', function () {
    $link = screeningLink('expired');

    $this->get(route('screening.show', $link->token))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('isOpen', false)
            ->where('fields', []),
        );
});

test('a not-accepting link renders the closed state', function () {
    $link = screeningLink('notAccepting');

    $this->get(route('screening.show', $link->token))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('isOpen', false)
            ->where('fields', []),
        );
});

test('an unknown token 404s', function () {
    $this->get(route('screening.show', 'this-token-does-not-exist'))
        ->assertNotFound();
});
