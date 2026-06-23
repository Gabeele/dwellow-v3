<?php

use App\Enums\FieldType;
use App\Screening\DefaultApplicationForm;

test('it returns the expected number of default fields', function () {
    expect(DefaultApplicationForm::fields())->toHaveCount(24);
});

test('every field has the required keys and a valid field type', function () {
    $validTypes = array_map(fn (FieldType $type) => $type->value, FieldType::cases());

    foreach (DefaultApplicationForm::fields() as $field) {
        expect($field)->toHaveKeys(['key', 'type', 'label', 'required', 'help', 'options']);
        expect($field['key'])->toBeString()->not->toBeEmpty();
        expect($field['label'])->toBeString()->not->toBeEmpty();
        expect($field['required'])->toBeBool();
        expect($field['type'])->toBeIn($validTypes);
    }
});

test('field keys are unique', function () {
    $keys = array_column(DefaultApplicationForm::fields(), 'key');

    expect($keys)->toEqual(array_unique($keys));
});

test('choice fields carry options and others do not', function () {
    foreach (DefaultApplicationForm::fields() as $field) {
        $type = FieldType::from($field['type']);

        if ($type->expectsOptions()) {
            expect($field['options'])->toBeArray()->not->toBeEmpty();
        } else {
            expect($field['options'])->toBeNull();
        }
    }
});

test('a required screening consent field is present', function () {
    $consent = collect(DefaultApplicationForm::fields())
        ->firstWhere('key', 'screening_consent');

    expect($consent)->not->toBeNull();
    expect($consent['type'])->toBe(FieldType::Consent->value);
    expect($consent['required'])->toBeTrue();
});

test('it does not collect a social insurance number', function () {
    $keys = array_column(DefaultApplicationForm::fields(), 'key');

    expect($keys)->not->toContain('sin')
        ->and($keys)->not->toContain('social_insurance_number');
});
