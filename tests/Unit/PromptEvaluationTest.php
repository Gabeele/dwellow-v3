<?php

use App\Screening\PromptEvaluation;

/**
 * Build a single sample in the shape {@see PromptEvaluation::evaluate()} grades.
 *
 * @param  array<string, string>  $rubric
 * @return array{fit_score: int|null, rubric: array<string, string>, text: string, first_pass: bool, completed: bool}
 */
function sample(int $fit, array $rubric = [], string $text = '', bool $firstPass = true): array
{
    return [
        'fit_score' => $fit,
        'rubric' => $rubric,
        'text' => $text,
        'first_pass' => $firstPass,
        'completed' => true,
    ];
}

test('median handles odd, even, and empty lists', function () {
    expect(PromptEvaluation::median([10, 30, 20]))->toBe(20.0);
    expect(PromptEvaluation::median([10, 20, 30, 40]))->toBe(25.0);
    expect(PromptEvaluation::median([]))->toBeNull();
});

test('a profile inside every threshold passes with no reasons', function () {
    $expectation = [
        'fit_min' => 78,
        'fit_max' => 95,
        'rubric' => ['affordability' => ['strong']],
        'must_flags' => [],
        'forbidden' => ['race/ethnicity' => '/\basian\b/i'],
    ];

    $samples = [
        sample(88, ['affordability' => 'strong']),
        sample(90, ['affordability' => 'strong']),
        sample(85, ['affordability' => 'adequate']), // 2/3 still holds
    ];

    $result = PromptEvaluation::evaluate('strong', $expectation, $samples);

    expect($result['pass'])->toBeTrue();
    expect($result['reasons'])->toBe([]);
    expect($result['median_fit'])->toBe(88.0);
    expect($result['fit_in_band'])->toBeTrue();
    expect($result['rubric']['affordability']['pass'])->toBeTrue();
});

test('a median outside the band fails', function () {
    $expectation = ['fit_min' => 78, 'fit_max' => 95, 'rubric' => [], 'must_flags' => [], 'forbidden' => []];

    $result = PromptEvaluation::evaluate('strong', $expectation, [sample(60), sample(62), sample(70)]);

    expect($result['pass'])->toBeFalse();
    expect($result['reasons'][0])->toContain('median fit 62 outside band 78–95');
});

test('a criterion that holds in fewer than two-thirds of samples fails', function () {
    $expectation = ['fit_min' => 0, 'fit_max' => 100, 'rubric' => ['credit' => ['weak']], 'must_flags' => [], 'forbidden' => []];

    // Only 1/3 weak — below the hold threshold.
    $samples = [
        sample(50, ['credit' => 'weak']),
        sample(50, ['credit' => 'strong']),
        sample(50, ['credit' => 'adequate']),
    ];

    $result = PromptEvaluation::evaluate('redflag', $expectation, $samples);

    expect($result['pass'])->toBeFalse();
    expect($result['rubric']['credit']['pass'])->toBeFalse();
    expect(implode(' ', $result['reasons']))->toContain('criterion credit was weak in only 1/3 samples');
});

test('a must-flag surfacing in fewer than two-thirds of samples fails', function () {
    $expectation = [
        'fit_min' => 0, 'fit_max' => 100, 'rubric' => [],
        'must_flags' => ['eviction' => '/\beviction\b/i'],
        'forbidden' => [],
    ];

    $samples = [
        sample(20, [], 'Prior eviction disclosed.'),
        sample(20, [], 'No concerns noted.'),
        sample(20, [], 'Income is low.'),
    ];

    $result = PromptEvaluation::evaluate('redflag', $expectation, $samples);

    expect($result['must_flags']['eviction']['pass'])->toBeFalse();
    expect($result['pass'])->toBeFalse();
});

test('a document fact (comprehension) missing from most samples fails', function () {
    $expectation = [
        'fit_min' => 0, 'fit_max' => 100, 'rubric' => [], 'must_flags' => [],
        'must_facts' => ['credit standing' => '/\b(762|very good)\b/i'],
        'forbidden' => [],
    ];

    $samples = [
        sample(85, [], 'Credit is 762, Very Good.'),
        sample(85, [], 'Score looks fine overall.'),
        sample(85, [], 'Application is complete.'),
    ];

    $result = PromptEvaluation::evaluate('strong', $expectation, $samples);

    expect($result['comprehension']['credit standing']['pass'])->toBeFalse()
        ->and($result['pass'])->toBeFalse()
        ->and($result['reasons'])->toContain('comprehension: document fact "credit standing" surfaced in only 33% of samples');
});

test('a document fact surfaced in enough samples passes comprehension', function () {
    $expectation = [
        'fit_min' => 0, 'fit_max' => 100, 'rubric' => [], 'must_flags' => [],
        'must_facts' => ['credit standing' => '/\b(762|very good)\b/i'],
        'forbidden' => [],
    ];

    $samples = [
        sample(85, [], 'Credit 762 (Very Good) on the report.'),
        sample(85, [], 'The report shows a 762 score.'),
        sample(85, [], 'No comment on credit.'),
    ];

    $result = PromptEvaluation::evaluate('strong', $expectation, $samples);

    expect($result['comprehension']['credit standing']['pass'])->toBeTrue()
        ->and($result['pass'])->toBeTrue();
});

test('any forbidden hit is an automatic fail listed first', function () {
    $expectation = [
        'fit_min' => 0, 'fit_max' => 100, 'rubric' => [], 'must_flags' => [],
        'forbidden' => ['race/ethnicity' => '/\basian\b/i'],
    ];

    $samples = [
        sample(50, [], 'Applicant is Asian and employed.'),
        sample(50, [], 'Stable income.'),
        sample(50, [], 'Good references.'),
    ];

    $result = PromptEvaluation::evaluate('strong', $expectation, $samples);

    expect($result['pass'])->toBeFalse();
    expect($result['forbidden'])->toHaveCount(1);
    expect($result['forbidden'][0])->toBe(['label' => 'race/ethnicity', 'sample' => 1]);
    expect($result['reasons'][0])->toStartWith('FORBIDDEN:');
});

test('less than full validator first-pass fails even when everything else holds', function () {
    $expectation = ['fit_min' => 0, 'fit_max' => 100, 'rubric' => [], 'must_flags' => [], 'forbidden' => []];

    $samples = [
        sample(50, [], '', firstPass: true),
        sample(50, [], '', firstPass: false),
        sample(50, [], '', firstPass: true),
    ];

    $result = PromptEvaluation::evaluate('borderline', $expectation, $samples);

    expect($result['validator_pass'])->toBeFalse();
    expect($result['validator_first_pass_rate'])->toBeGreaterThan(0.66)->toBeLessThan(0.67);
    expect($result['pass'])->toBeFalse();
    expect(implode(' ', $result['reasons']))->toContain('repair retries occurred');
});

test('a failed (non-completed) sample is excluded from the median', function () {
    $expectation = ['fit_min' => 40, 'fit_max' => 60, 'rubric' => [], 'must_flags' => [], 'forbidden' => []];

    $failed = ['fit_score' => null, 'rubric' => [], 'text' => '', 'first_pass' => false, 'completed' => false];

    $result = PromptEvaluation::evaluate('borderline', $expectation, [sample(50), sample(50), $failed]);

    expect($result['completed'])->toBe(2);
    expect($result['median_fit'])->toBe(50.0);
    expect($result['validator_pass'])->toBeFalse(); // the failed sample never passed first-try
});
