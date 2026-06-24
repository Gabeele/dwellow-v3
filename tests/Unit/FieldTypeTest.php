<?php

use App\Enums\FieldType;

test('field types report their label', function () {
    expect(FieldType::ShortText->label())->toBe('Short text');
    expect(FieldType::SingleChoice->label())->toBe('Single choice');
    expect(FieldType::Consent->label())->toBe('Consent');
});

test('only choice field types expect options', function () {
    expect(FieldType::SingleChoice->expectsOptions())->toBeTrue();
    expect(FieldType::MultiChoice->expectsOptions())->toBeTrue();
    expect(FieldType::ShortText->expectsOptions())->toBeFalse();
    expect(FieldType::File->expectsOptions())->toBeFalse();
});

test('only the file field type is a file upload', function () {
    expect(FieldType::File->isFileUpload())->toBeTrue();
    expect(FieldType::ShortText->isFileUpload())->toBeFalse();
    expect(FieldType::Reference->isFileUpload())->toBeFalse();
});
