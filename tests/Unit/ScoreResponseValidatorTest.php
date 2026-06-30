<?php

use App\Screening\ScoreResponseValidator;

/**
 * A complete, contract-valid payload to vary in individual tests.
 *
 * @return array<string, mixed>
 */
function validScorePayload(array $overrides = []): array
{
    return array_replace([
        'fit_score' => 82,
        'score_rationale' => 'Stable income and complete application.',
        'summary' => 'The applicant reports steady employment. References were provided. No disclosed concerns.',
        'red_flags' => ['Move-in date is sooner than the unit is available.'],
        'strengths' => ['Rent-to-income ratio is comfortable.'],
    ], $overrides);
}

test('a contract-valid payload passes and returns the normalised value', function () {
    $result = (new ScoreResponseValidator)->validate(validScorePayload());

    expect($result->valid)->toBeTrue();
    expect($result->errors)->toBe([]);
    expect($result->value)->toBe([
        'fit_score' => 82,
        'score_rationale' => 'Stable income and complete application.',
        'summary' => 'The applicant reports steady employment. References were provided. No disclosed concerns.',
        'red_flags' => ['Move-in date is sooner than the unit is available.'],
        'strengths' => ['Rent-to-income ratio is comfortable.'],
    ]);
});

test('empty flag and strength arrays are valid', function () {
    $result = (new ScoreResponseValidator)->validate(
        validScorePayload(['red_flags' => [], 'strengths' => []]),
    );

    expect($result->valid)->toBeTrue();
    expect($result->value['red_flags'])->toBe([]);
    expect($result->value['strengths'])->toBe([]);
});

test('extra keys the model emits are stripped from the value', function () {
    $result = (new ScoreResponseValidator)->validate(
        validScorePayload(['recommendation' => 'approve', 'protected_guess' => 'nope']),
    );

    expect($result->valid)->toBeTrue();
    expect($result->value)->not->toHaveKey('recommendation');
    expect($result->value)->not->toHaveKey('protected_guess');
});

test('an out-of-range fit_score fails', function () {
    $result = (new ScoreResponseValidator)->validate(validScorePayload(['fit_score' => 150]));

    expect($result->valid)->toBeFalse();
    expect($result->value)->toBeNull();
    expect($result->errors)->toContain('The fit_score field must be between 0 and 100.');
});

test('a missing required key fails', function () {
    $payload = validScorePayload();
    unset($payload['summary']);

    $result = (new ScoreResponseValidator)->validate($payload);

    expect($result->valid)->toBeFalse();
    expect($result->errors)->toContain('The summary field is required.');
});

test('wrong types fail — string fit_score, non-array flags, non-string strength items', function () {
    $result = (new ScoreResponseValidator)->validate(validScorePayload([
        'fit_score' => '82',
        'red_flags' => 'too risky',
        'strengths' => ['ok', 7],
    ]));

    expect($result->valid)->toBeFalse();
    expect($result->errors)
        ->toContain('The fit_score field must be an integer.')
        ->toContain('The red_flags field must be an array of strings.')
        ->toContain('The strengths field must be an array of strings.');
});

test('a non-array payload fails cleanly', function () {
    $result = (new ScoreResponseValidator)->validate('not json');

    expect($result->valid)->toBeFalse();
    expect($result->errors)->toBe(['The response must be a JSON object.']);
});
