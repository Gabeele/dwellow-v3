<?php

use App\Models\Property;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('the owning landlord can load the form-builder page with the units sections', function () {
    $landlord = User::factory()->landlord()->create();
    $property = Property::factory()->for($landlord, 'landlord')->create();
    $unit = Unit::factory()->for($property)->create();

    $this->withoutVite();

    $this->actingAs($landlord)
        ->get(route('units.form.edit', $unit))
        ->assertInertia(fn (Assert $page) => $page
            ->component('screening/forms/Edit')
            ->has('unit')
            ->has('sections', 8)
            ->where('sections.0.key', 'personal_information')
            ->where('sections.0.locked', true),
        );
});

test('the form-builder route linked from the screening panel is reachable for a whole-rental backing unit', function () {
    $landlord = User::factory()->landlord()->create();
    $property = Property::factory()->whole()->for($landlord, 'landlord')->create();
    $backingUnit = $property->units()->sole();

    $this->withoutVite();

    $this->actingAs($landlord)
        ->get(route('units.form.edit', $backingUnit))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('screening/forms/Edit')
            ->where('unit.id', $backingUnit->id)
            ->has('sections'),
        );
});

test('the owning landlord can disable an optional section and it persists', function () {
    $landlord = User::factory()->landlord()->create();
    $property = Property::factory()->for($landlord, 'landlord')->create();
    $unit = Unit::factory()->for($property)->create();

    // Keep only residence history (locked sections come along automatically).
    $this->actingAs($landlord)
        ->put(route('units.form.update', $unit), ['enabled_sections' => ['residence_history']])
        ->assertRedirect(route('units.form.edit', $unit));

    $sections = collect($unit->fresh()->applicationForm->sections);

    expect($sections->firstWhere('key', 'residence_history')['enabled'])->toBeTrue()
        ->and($sections->firstWhere('key', 'background_check')['enabled'])->toBeFalse()
        // Locked sections are always enabled, even when omitted from the payload.
        ->and($sections->firstWhere('key', 'personal_information')['enabled'])->toBeTrue()
        ->and($sections->firstWhere('key', 'consent')['enabled'])->toBeTrue();
});

test('a disabled section can be re-enabled', function () {
    $landlord = User::factory()->landlord()->create();
    $property = Property::factory()->for($landlord, 'landlord')->create();
    $unit = Unit::factory()->for($property)->create();

    $this->actingAs($landlord)
        ->put(route('units.form.update', $unit), ['enabled_sections' => []]);

    expect(collect($unit->fresh()->applicationForm->sections)
        ->firstWhere('key', 'employment_income')['enabled'])->toBeFalse();

    $this->actingAs($landlord)
        ->put(route('units.form.update', $unit), ['enabled_sections' => ['employment_income']]);

    expect(collect($unit->fresh()->applicationForm->sections)
        ->firstWhere('key', 'employment_income')['enabled'])->toBeTrue();
});

test('an unknown section key is rejected', function () {
    $landlord = User::factory()->landlord()->create();
    $property = Property::factory()->for($landlord, 'landlord')->create();
    $unit = Unit::factory()->for($property)->create();

    $this->actingAs($landlord)
        ->put(route('units.form.update', $unit), ['enabled_sections' => ['not_a_section']])
        ->assertSessionHasErrors('enabled_sections.0');
});

test('a non-owner cannot view or update another landlords form', function () {
    $landlord = User::factory()->landlord()->create();
    $unit = Unit::factory()->create();

    $this->actingAs($landlord)
        ->get(route('units.form.edit', $unit))
        ->assertForbidden();

    $this->actingAs($landlord)
        ->put(route('units.form.update', $unit), ['enabled_sections' => ['residence_history']])
        ->assertForbidden();
});
