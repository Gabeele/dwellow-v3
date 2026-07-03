<?php

use App\Enums\CriterionAssessment;
use App\Screening\ScoringFramework;

/**
 * Guards the machine-readable ground truth the prompt-tuning harness relies on.
 * A malformed expectations file (bad rubric key, invalid assessment, uncompilable
 * regex) would make every `screening:eval-prompt` run meaningless, so lock the shape.
 */
$expectations = require dirname(__DIR__).'/Fixtures/screening-samples/expectations.php';

it('covers exactly the three sample profiles', function () use ($expectations) {
    expect(array_keys($expectations))
        ->toEqualCanonicalizing(['strong', 'borderline', 'redflag']);
});

it('gives each profile a valid rent and fit band', function () use ($expectations) {
    foreach ($expectations as $profile => $spec) {
        expect($spec['rent'])->toBeInt()->toBeGreaterThan(0, "{$profile} rent");
        expect($spec['fit_min'])->toBeInt()->toBeGreaterThanOrEqual(0);
        expect($spec['fit_max'])->toBeInt()->toBeLessThanOrEqual(100);
        expect($spec['fit_min'])->toBeLessThan($spec['fit_max'], "{$profile} band");
    }
});

it('only asserts real rubric criteria with valid assessments', function () use ($expectations) {
    $criteria = ScoringFramework::keys();
    $assessments = CriterionAssessment::values();

    foreach ($expectations as $profile => $spec) {
        foreach ($spec['rubric'] as $criterion => $allowed) {
            expect($criteria)->toContain($criterion);
            expect($allowed)->not->toBeEmpty("{$profile}.{$criterion}");
            foreach ($allowed as $value) {
                expect($assessments)->toContain($value);
            }
        }
    }
});

it('has compilable must_flags, must_facts and forbidden regexes', function () use ($expectations) {
    foreach ($expectations as $profile => $spec) {
        foreach (['must_flags', 'must_facts', 'forbidden'] as $bucket) {
            foreach ($spec[$bucket] ?? [] as $label => $pattern) {
                expect(@preg_match($pattern, ''))
                    ->not->toBeFalse("{$profile}.{$bucket}.{$label} is not a valid regex");
            }
        }
    }
});

it('gives every profile at least one document-comprehension fact', function () use ($expectations) {
    foreach ($expectations as $profile => $spec) {
        expect($spec['must_facts'] ?? [])
            ->not->toBeEmpty("{$profile} should reward reading its documents");
    }
});

it('forbids protected-class language for every profile', function () use ($expectations) {
    foreach ($expectations as $profile => $spec) {
        expect($spec['forbidden'])->toHaveKey('race/ethnicity');
        expect($spec['forbidden'])->toHaveKey('disability');
    }
});
