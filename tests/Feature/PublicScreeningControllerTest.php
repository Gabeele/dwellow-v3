<?php

use App\Models\Application;
use App\Models\ApplicationLink;
use App\Models\Property;
use App\Models\Unit;
use App\Screening\DefaultApplicationForm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

test('each rendered section carries its heading and grouped fields', function () {
    $link = screeningLink();

    // The applicant sees section headings (label + description) with the section's
    // fields grouped beneath, not a flat field list.
    $this->get(route('screening.show', $link->token))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('sections.0.key', 'personal_information')
            ->where('sections.0.label', 'Personal information')
            ->has('sections.0.description')
            ->has('sections.0.fields')
            ->where('sections.0.fields.0.key', 'first_name')
            ->has('sections.0.fields.0.type')
            ->has('sections.0.fields.0.label')
            ->has('sections.0.fields.0.required'),
        );
});

test('fields carry the required flag and inline help the form renders', function () {
    $link = screeningLink();

    // The apply page draws its required-field markers from `required` and its
    // inline per-field help from `help`; assert both reach the client.
    $this->get(route('screening.show', $link->token))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('sections.0.fields.0.required', true)
            ->where('sections.1.fields.4.key', 'previous_landlord')
            ->where('sections.1.fields.4.help', fn ($help) => filled($help)),
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

test('a unit provisioned before the form observer still renders the default sections', function () {
    // Reproduces the original bug: units seeded before the application-form
    // observer existed have no form row, so the public page rendered zero
    // sections (a blank application). The controller now resolves-or-defaults.
    $link = screeningLink();
    $link->unit->applicationForm()->delete();

    expect($link->unit->fresh()->applicationForm)->toBeNull();

    $this->get(route('screening.show', $link->token))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('screening/Apply')
            ->where('isOpen', true)
            ->where('sections.0.key', 'personal_information')
            ->has('sections', count(DefaultApplicationForm::sections())),
        );

    // Resolving the default also heals the missing row for every later request.
    expect($link->unit->fresh()->applicationForm)->not->toBeNull();
});

test('a submission to a unit with no form row provisions the default and is accepted', function () {
    Storage::fake('local');

    $link = screeningLink();
    $link->unit->applicationForm()->delete();

    $this->post(route('screening.store', $link->token), [
        'answers' => [
            'first_name' => 'Jordan',
            'last_name' => 'Rivera',
            'email' => 'jordan.rivera@example.com',
            'phone' => '604-555-0142',
            'date_of_birth' => '1990-04-15',
            'current_address' => '12 Maple Street, Vancouver, BC',
            'employer_name' => 'Acme Co',
            'gross_monthly_income' => '5000',
            'desired_move_in_date' => '2026-08-01',
            'number_of_occupants' => '2',
            'screening_consent' => '1',
            'photo_id' => UploadedFile::fake()->create('id.pdf', 120, 'application/pdf'),
            'pay_stubs' => UploadedFile::fake()->create('stubs.pdf', 200, 'application/pdf'),
        ],
    ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('screening.submitted', $link->token));

    expect(Application::query()->count())->toBe(1);
});

test('a revoked link renders the closed state with its reason', function () {
    $link = screeningLink('revoked');

    $this->get(route('screening.show', $link->token))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('screening/Apply')
            ->where('isOpen', false)
            ->where('closedReason', 'revoked')
            ->where('sections', []),
        );
});

test('an expired link renders the closed state with its reason', function () {
    $link = screeningLink('expired');

    $this->get(route('screening.show', $link->token))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('isOpen', false)
            ->where('closedReason', 'expired')
            ->where('sections', []),
        );
});

test('a not-accepting link renders the closed state with its reason', function () {
    $link = screeningLink('notAccepting');

    $this->get(route('screening.show', $link->token))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('isOpen', false)
            ->where('closedReason', 'not_accepting')
            ->where('sections', []),
        );
});

test('an open link reports no closed reason', function () {
    $link = screeningLink();

    $this->get(route('screening.show', $link->token))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('isOpen', true)
            ->where('closedReason', null),
        );
});

test('an unknown token 404s', function () {
    $this->get(route('screening.show', 'this-token-does-not-exist'))
        ->assertNotFound();
});
