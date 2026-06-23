<?php

use App\Enums\FieldType;
use App\Screening\DefaultApplicationForm;

/**
 * Every field across every section, flattened.
 *
 * @return array<int, array<string, mixed>>
 */
function allDefaultFields(): array
{
    return collect(DefaultApplicationForm::sections())
        ->flatMap(fn (array $section): array => $section['fields'])
        ->all();
}

test('it groups fields into named sections', function () {
    $sections = DefaultApplicationForm::sections();

    expect($sections)->not->toBeEmpty();

    foreach ($sections as $section) {
        expect($section)->toHaveKeys(['key', 'label', 'description', 'locked', 'enabled', 'fields']);
        expect($section['key'])->toBeString()->not->toBeEmpty();
        expect($section['label'])->toBeString()->not->toBeEmpty();
        expect($section['locked'])->toBeBool();
        expect($section['enabled'])->toBeTrue();
        expect($section['fields'])->toBeArray()->not->toBeEmpty();
    }
});

test('section keys are unique', function () {
    $keys = DefaultApplicationForm::sectionKeys();

    expect($keys)->toEqual(array_unique($keys));
});

test('personal information and consent sections are locked', function () {
    $locked = collect(DefaultApplicationForm::sections())
        ->where('locked', true)
        ->pluck('key');

    expect($locked)->toContain('personal_information')
        ->and($locked)->toContain('consent');
});

test('every field has the required keys and a valid field type', function () {
    $validTypes = array_map(fn (FieldType $type) => $type->value, FieldType::cases());

    foreach (allDefaultFields() as $field) {
        expect($field)->toHaveKeys(['key', 'type', 'label', 'required', 'help', 'options']);
        expect($field['key'])->toBeString()->not->toBeEmpty();
        expect($field['label'])->toBeString()->not->toBeEmpty();
        expect($field['required'])->toBeBool();
        expect($field['type'])->toBeIn($validTypes);
    }
});

test('field keys are unique across all sections', function () {
    $keys = array_column(allDefaultFields(), 'key');

    expect($keys)->toEqual(array_unique($keys));
});

test('choice fields carry options and others do not', function () {
    foreach (allDefaultFields() as $field) {
        $type = FieldType::from($field['type']);

        if ($type->expectsOptions()) {
            expect($field['options'])->toBeArray()->not->toBeEmpty();
        } else {
            expect($field['options'])->toBeNull();
        }
    }
});

test('a required screening consent field is present', function () {
    $consent = collect(allDefaultFields())->firstWhere('key', 'screening_consent');

    expect($consent)->not->toBeNull();
    expect($consent['type'])->toBe(FieldType::Consent->value);
    expect($consent['required'])->toBeTrue();
});

test('it does not collect a social insurance number', function () {
    $keys = array_column(allDefaultFields(), 'key');

    expect($keys)->not->toContain('sin')
        ->and($keys)->not->toContain('social_insurance_number');
});

test('withEnabledSections enables only the chosen sections plus locked ones', function () {
    $sections = collect(DefaultApplicationForm::withEnabledSections(['residence_history']));

    // The selected section is on.
    expect($sections->firstWhere('key', 'residence_history')['enabled'])->toBeTrue();

    // Locked sections stay on regardless of the selection.
    expect($sections->firstWhere('key', 'personal_information')['enabled'])->toBeTrue();
    expect($sections->firstWhere('key', 'consent')['enabled'])->toBeTrue();

    // An unselected, unlocked section is off.
    expect($sections->firstWhere('key', 'background_check')['enabled'])->toBeFalse();
});
