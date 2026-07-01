<?php

use App\Screening\ScoreResponseValidator;

/**
 * The eight framework criteria graded — a complete, valid rubric.
 *
 * @return list<array{criterion: string, assessment: string, note: string}>
 */
function fullRubric(): array
{
    return [
        ['criterion' => 'affordability', 'assessment' => 'strong', 'note' => '~26% of gross'],
        ['criterion' => 'employment', 'assessment' => 'adequate', 'note' => '2 years'],
        ['criterion' => 'credit', 'assessment' => 'adequate', 'note' => 'fair'],
        ['criterion' => 'references', 'assessment' => 'unverified', 'note' => 'none provided'],
        ['criterion' => 'rental_history', 'assessment' => 'adequate', 'note' => 'no issues'],
        ['criterion' => 'occupancy', 'assessment' => 'strong', 'note' => '2 in a 3-bed'],
        ['criterion' => 'identity', 'assessment' => 'strong', 'note' => 'ID matches'],
        ['criterion' => 'disclosures', 'assessment' => 'adequate', 'note' => 'no pets'],
    ];
}

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
        'rubric' => fullRubric(),
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
        'rubric' => fullRubric(),
        'red_flags' => ['Move-in date is sooner than the unit is available.'],
        'strengths' => ['Rent-to-income ratio is comfortable.'],
    ]);
});

test('the rubric is required', function () {
    $missing = validScorePayload();
    unset($missing['rubric']);

    expect((new ScoreResponseValidator)->validate($missing)->errors)
        ->toContain('The rubric field is required.');
});

test('the rubric must grade every framework criterion', function () {
    $partial = array_values(array_filter(fullRubric(), fn (array $r): bool => $r['criterion'] !== 'identity'));

    $result = (new ScoreResponseValidator)->validate(validScorePayload(['rubric' => $partial]));

    expect($result->valid)->toBeFalse();
    expect($result->value)->toBeNull();
});

test('an unknown criterion or an invalid assessment fails', function () {
    $badCriterion = fullRubric();
    $badCriterion[0]['criterion'] = 'vibes';
    expect((new ScoreResponseValidator)->validate(validScorePayload(['rubric' => $badCriterion]))->valid)->toBeFalse();

    $badAssessment = fullRubric();
    $badAssessment[0]['assessment'] = 'great';
    expect((new ScoreResponseValidator)->validate(validScorePayload(['rubric' => $badAssessment]))->valid)->toBeFalse();
});

test('the rubric is lower-cased, noteless rows default to empty, and rows are re-ordered canonically', function () {
    $shuffled = array_reverse(fullRubric());
    $shuffled[0]['assessment'] = 'STRONG';   // disclosures, upper-cased
    unset($shuffled[1]['note']);             // identity, no note
    $shuffled[2]['extra'] = 'ignored';       // occupancy, extra key

    $result = (new ScoreResponseValidator)->validate(validScorePayload(['rubric' => $shuffled]));

    expect($result->valid)->toBeTrue();
    expect(array_column($result->value['rubric'], 'criterion'))
        ->toBe(['affordability', 'employment', 'credit', 'references', 'rental_history', 'occupancy', 'identity', 'disclosures']);

    foreach ($result->value['rubric'] as $row) {
        expect($row)->toHaveKeys(['criterion', 'assessment', 'note'])->not->toHaveKey('extra');
        expect($row['assessment'])->toBe(strtolower($row['assessment']));
    }

    $identity = collect($result->value['rubric'])->firstWhere('criterion', 'identity');
    expect($identity['note'])->toBe('');
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
