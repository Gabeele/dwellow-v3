<?php

use App\Models\Property;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

/**
 * A valid application-form schema payload.
 *
 * @return array<string, mixed>
 */
function formSchemaPayload(array $overrides = []): array
{
    return array_merge([
        'fields' => [
            [
                'key' => 'first_name',
                'type' => 'short_text',
                'label' => 'First name',
                'required' => true,
                'help' => null,
                'options' => null,
            ],
            [
                'key' => 'employment_type',
                'type' => 'single_choice',
                'label' => 'Employment type',
                'required' => false,
                'help' => null,
                'options' => ['Full-time', 'Part-time'],
            ],
        ],
    ], $overrides);
}

test('the owning landlord can load the form-builder page with the units fields', function () {
    $landlord = User::factory()->landlord()->create();
    $property = Property::factory()->for($landlord, 'landlord')->create();
    $unit = Unit::factory()->for($property)->create();

    $this->withoutVite();

    $this->actingAs($landlord)
        ->get(route('units.form.edit', $unit))
        ->assertInertia(fn (Assert $page) => $page
            ->component('screening/forms/Edit')
            ->has('unit')
            ->has('fields')
            ->has('fieldTypes', 11)
            ->has('defaultFields'),
        );
});

test('the owning landlord can update the form schema and it persists', function () {
    $landlord = User::factory()->landlord()->create();
    $property = Property::factory()->for($landlord, 'landlord')->create();
    $unit = Unit::factory()->for($property)->create();

    $this->actingAs($landlord)
        ->put(route('units.form.update', $unit), formSchemaPayload())
        ->assertRedirect(route('units.form.edit', $unit));

    $unit->refresh();
    $fields = $unit->applicationForm->fields;

    expect($fields)->toHaveCount(2)
        ->and($fields[0]['key'])->toBe('first_name')
        ->and($fields[1]['options'])->toBe(['Full-time', 'Part-time']);
});

test('a duplicate field key is rejected', function () {
    $landlord = User::factory()->landlord()->create();
    $property = Property::factory()->for($landlord, 'landlord')->create();
    $unit = Unit::factory()->for($property)->create();

    $payload = formSchemaPayload(['fields' => [
        ['key' => 'name', 'type' => 'short_text', 'label' => 'A', 'required' => true, 'help' => null, 'options' => null],
        ['key' => 'name', 'type' => 'short_text', 'label' => 'B', 'required' => true, 'help' => null, 'options' => null],
    ]]);

    $this->actingAs($landlord)
        ->put(route('units.form.update', $unit), $payload)
        ->assertSessionHasErrors('fields.1.key');
});

test('an invalid field type is rejected', function () {
    $landlord = User::factory()->landlord()->create();
    $property = Property::factory()->for($landlord, 'landlord')->create();
    $unit = Unit::factory()->for($property)->create();

    $payload = formSchemaPayload(['fields' => [
        ['key' => 'name', 'type' => 'not_a_type', 'label' => 'A', 'required' => true, 'help' => null, 'options' => null],
    ]]);

    $this->actingAs($landlord)
        ->put(route('units.form.update', $unit), $payload)
        ->assertSessionHasErrors('fields.0.type');
});

test('options on a non-option field type are rejected', function () {
    $landlord = User::factory()->landlord()->create();
    $property = Property::factory()->for($landlord, 'landlord')->create();
    $unit = Unit::factory()->for($property)->create();

    $payload = formSchemaPayload(['fields' => [
        ['key' => 'name', 'type' => 'short_text', 'label' => 'A', 'required' => true, 'help' => null, 'options' => ['x', 'y']],
    ]]);

    $this->actingAs($landlord)
        ->put(route('units.form.update', $unit), $payload)
        ->assertSessionHasErrors('fields.0.options');
});

test('a choice field with no options is rejected', function () {
    $landlord = User::factory()->landlord()->create();
    $property = Property::factory()->for($landlord, 'landlord')->create();
    $unit = Unit::factory()->for($property)->create();

    $payload = formSchemaPayload(['fields' => [
        ['key' => 'choice', 'type' => 'single_choice', 'label' => 'A', 'required' => true, 'help' => null, 'options' => []],
    ]]);

    $this->actingAs($landlord)
        ->put(route('units.form.update', $unit), $payload)
        ->assertSessionHasErrors('fields.0.options');
});

test('a non-owner cannot view or update another landlords form', function () {
    $landlord = User::factory()->landlord()->create();
    $unit = Unit::factory()->create();

    $this->actingAs($landlord)
        ->get(route('units.form.edit', $unit))
        ->assertForbidden();

    $this->actingAs($landlord)
        ->put(route('units.form.update', $unit), formSchemaPayload())
        ->assertForbidden();
});
