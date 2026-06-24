<?php

use App\Models\Application;
use App\Models\ApplicationLink;
use App\Models\Property;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

/**
 * Build a landlord with a property, a unit (auto-provisioned application form),
 * an application link, and one submitted application — enough to render every
 * screening page with real data.
 *
 * @return array{landlord: User, property: Property, unit: Unit, link: ApplicationLink, application: Application}
 */
function screeningFixture(): array
{
    $landlord = User::factory()->landlord()->create();
    $property = Property::factory()->for($landlord, 'landlord')->create();
    $unit = Unit::factory()->for($property)->create();
    $link = ApplicationLink::factory()->for($unit)->create();
    $application = Application::factory()->for($link, 'applicationLink')->create();

    return compact('landlord', 'property', 'unit', 'link', 'application');
}

beforeEach(function () {
    $this->withoutVite();
});

test('the landlord screening pages render without error', function (string $routeName, callable $params, string $component) {
    $fixture = screeningFixture();

    $this->actingAs($fixture['landlord'])
        ->get(route($routeName, $params($fixture)))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component($component));
})->with([
    'properties show' => ['properties.show', fn (array $f) => $f['property'], 'properties/Show'],
    'form builder' => ['units.form.edit', fn (array $f) => $f['unit'], 'screening/forms/Edit'],
    'per-unit applicants' => ['units.applicants.index', fn (array $f) => $f['unit'], 'screening/applicants/Index'],
    'property applicants' => ['properties.applicants.index', fn (array $f) => $f['property'], 'screening/applicants/Property'],
    'all applications' => ['applications.index', fn (array $f) => [], 'screening/applicants/All'],
    'applicant detail' => ['applicants.show', fn (array $f) => $f['application'], 'screening/applicants/Show'],
]);

test('the public apply page renders without error', function () {
    $fixture = screeningFixture();

    $this->get(route('screening.show', $fixture['link']->token))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('screening/Apply'));
});
