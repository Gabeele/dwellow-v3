<?php

namespace App\Screening;

/**
 * Pure scoring logic for the `screening:eval-prompt` harness: given one profile's
 * expectation (from `tests/Fixtures/screening-samples/expectations.php`) and the
 * set of {@see Score} samples the model produced for it, decide PASS/FAIL and
 * explain why.
 *
 * It touches no database, container, or model — only arrays — so the harness's
 * judgment is unit-testable without the live model. The command owns the slow,
 * side-effecting half (build applications, call the model); this owns the maths.
 *
 * The thresholds mirror the exit criteria in `ralph.md`: a profile passes when its
 * MEDIAN fit_score sits in band, every expected rubric assessment and must-flag
 * holds in at least two-thirds of samples, no forbidden (protected-class) language
 * appears in ANY sample, and every sample passed the validator on the first try.
 */
class PromptEvaluation
{
    /**
     * The share of samples an assessment or must-flag must hold in to count. A
     * two-thirds gate tolerates the model's nondeterminism without letting a
     * coin-flip criterion pass (e.g. 2/3, or 4/5 — never 3/5).
     */
    public const HOLD_THRESHOLD = 2 / 3;

    /**
     * Evaluate one profile's samples against its expectation.
     *
     * @param  array{fit_min: int, fit_max: int, rubric?: array<string, list<string>>, must_flags?: array<string, string>, forbidden?: array<string, string>}  $expectation
     * @param  list<array{fit_score: int|null, rubric: array<string, string>, text: string, first_pass: bool, completed: bool}>  $samples
     * @return array{
     *     profile: string,
     *     samples: int,
     *     completed: int,
     *     median_fit: float|null,
     *     fit_min: int,
     *     fit_max: int,
     *     fit_in_band: bool,
     *     rubric: array<string, array{allowed: list<string>, distribution: array<string, int>, hits: int, rate: float, pass: bool}>,
     *     must_flags: array<string, array{hits: int, rate: float, pass: bool}>,
     *     forbidden: list<array{label: string, sample: int}>,
     *     validator_first_pass_rate: float,
     *     validator_pass: bool,
     *     pass: bool,
     *     reasons: list<string>
     * }
     */
    public static function evaluate(string $profile, array $expectation, array $samples): array
    {
        $total = count($samples);

        $fitScores = [];
        foreach ($samples as $sample) {
            if ($sample['completed'] && $sample['fit_score'] !== null) {
                $fitScores[] = $sample['fit_score'];
            }
        }

        $median = self::median($fitScores);
        $fitInBand = $median !== null
            && $median >= $expectation['fit_min']
            && $median <= $expectation['fit_max'];

        $rubric = self::gradeRubric($expectation['rubric'] ?? [], $samples);
        $mustFlags = self::gradeMustFlags($expectation['must_flags'] ?? [], $samples);
        $forbidden = self::scanForbidden($expectation['forbidden'] ?? [], $samples);

        $firstPassCount = count(array_filter($samples, fn (array $s): bool => $s['first_pass']));
        $firstPassRate = $total === 0 ? 0.0 : $firstPassCount / $total;
        $validatorPass = $total > 0 && $firstPassCount === $total;

        $reasons = self::reasons($median, $expectation, $fitInBand, $rubric, $mustFlags, $forbidden, $firstPassRate, $validatorPass);

        return [
            'profile' => $profile,
            'samples' => $total,
            'completed' => count($fitScores),
            'median_fit' => $median,
            'fit_min' => $expectation['fit_min'],
            'fit_max' => $expectation['fit_max'],
            'fit_in_band' => $fitInBand,
            'rubric' => $rubric,
            'must_flags' => $mustFlags,
            'forbidden' => $forbidden,
            'validator_first_pass_rate' => $firstPassRate,
            'validator_pass' => $validatorPass,
            'pass' => $reasons === [],
            'reasons' => $reasons,
        ];
    }

    /**
     * Grade each expected criterion: how the sampled assessments distribute, how
     * often the assessment lands in the allowed set, and whether that clears the
     * hold threshold.
     *
     * @param  array<string, list<string>>  $expected  criterion ⇒ allowed assessments
     * @param  list<array{rubric: array<string, string>}>  $samples
     * @return array<string, array{allowed: list<string>, distribution: array<string, int>, hits: int, rate: float, pass: bool}>
     */
    private static function gradeRubric(array $expected, array $samples): array
    {
        $total = count($samples);
        $graded = [];

        foreach ($expected as $criterion => $allowed) {
            $distribution = [];
            $hits = 0;

            foreach ($samples as $sample) {
                $assessment = $sample['rubric'][$criterion] ?? '—';
                $distribution[$assessment] = ($distribution[$assessment] ?? 0) + 1;

                if (in_array($assessment, $allowed, true)) {
                    $hits++;
                }
            }

            $rate = $total === 0 ? 0.0 : $hits / $total;

            $graded[$criterion] = [
                'allowed' => $allowed,
                'distribution' => $distribution,
                'hits' => $hits,
                'rate' => $rate,
                'pass' => $rate >= self::HOLD_THRESHOLD,
            ];
        }

        return $graded;
    }

    /**
     * Grade each must-flag: how many samples surface the concern in their
     * assessment text, and whether that clears the hold threshold.
     *
     * @param  array<string, string>  $mustFlags  label ⇒ case-insensitive regex
     * @param  list<array{text: string}>  $samples
     * @return array<string, array{hits: int, rate: float, pass: bool}>
     */
    private static function gradeMustFlags(array $mustFlags, array $samples): array
    {
        $total = count($samples);
        $graded = [];

        foreach ($mustFlags as $label => $pattern) {
            $hits = 0;

            foreach ($samples as $sample) {
                if (preg_match($pattern, $sample['text']) === 1) {
                    $hits++;
                }
            }

            $rate = $total === 0 ? 0.0 : $hits / $total;

            $graded[$label] = [
                'hits' => $hits,
                'rate' => $rate,
                'pass' => $rate >= self::HOLD_THRESHOLD,
            ];
        }

        return $graded;
    }

    /**
     * Record every forbidden (protected-class) match across every sample. Any hit
     * is an automatic fail, so each is captured with the offending sample number.
     *
     * @param  array<string, string>  $forbidden  label ⇒ case-insensitive regex
     * @param  list<array{text: string}>  $samples
     * @return list<array{label: string, sample: int}>
     */
    private static function scanForbidden(array $forbidden, array $samples): array
    {
        $hits = [];

        foreach ($samples as $index => $sample) {
            foreach ($forbidden as $label => $pattern) {
                if (preg_match($pattern, $sample['text']) === 1) {
                    $hits[] = ['label' => $label, 'sample' => $index + 1];
                }
            }
        }

        return $hits;
    }

    /**
     * Assemble the FAIL reasons, guardrail first: a forbidden hit outranks every
     * other gap, then out-of-band fit, mis-graded criteria, missing must-flags, and
     * validator repair retries. An empty list means the profile passed.
     *
     * @param  array{fit_min: int, fit_max: int}  $expectation
     * @param  array<string, array{allowed: list<string>, distribution: array<string, int>, hits: int, rate: float, pass: bool}>  $rubric
     * @param  array<string, array{hits: int, rate: float, pass: bool}>  $mustFlags
     * @param  list<array{label: string, sample: int}>  $forbidden
     * @return list<string>
     */
    private static function reasons(
        ?float $median,
        array $expectation,
        bool $fitInBand,
        array $rubric,
        array $mustFlags,
        array $forbidden,
        float $firstPassRate,
        bool $validatorPass,
    ): array {
        $reasons = [];

        foreach ($forbidden as $hit) {
            $reasons[] = "FORBIDDEN: protected-class language \"{$hit['label']}\" in sample {$hit['sample']}";
        }

        if (! $fitInBand) {
            $shown = $median === null ? 'n/a' : self::number($median);
            $reasons[] = "median fit {$shown} outside band {$expectation['fit_min']}–{$expectation['fit_max']}";
        }

        foreach ($rubric as $criterion => $grade) {
            if (! $grade['pass']) {
                $allowed = implode('/', $grade['allowed']);
                $total = array_sum($grade['distribution']);
                $reasons[] = "criterion {$criterion} was {$allowed} in only {$grade['hits']}/{$total} samples";
            }
        }

        foreach ($mustFlags as $label => $grade) {
            if (! $grade['pass']) {
                $reasons[] = "must-flag \"{$label}\" surfaced in only ".self::percent($grade['rate']).' of samples';
            }
        }

        if (! $validatorPass) {
            $reasons[] = 'validator first-pass rate '.self::percent($firstPassRate).' (repair retries occurred)';
        }

        return $reasons;
    }

    /**
     * The median of a list of integers, or null when the list is empty. For an
     * even count it averages the two middle values (so it can be fractional).
     *
     * @param  list<int>  $values
     */
    public static function median(array $values): ?float
    {
        if ($values === []) {
            return null;
        }

        sort($values);
        $count = count($values);
        $mid = intdiv($count, 2);

        if ($count % 2 === 1) {
            return (float) $values[$mid];
        }

        return ($values[$mid - 1] + $values[$mid]) / 2;
    }

    /**
     * Format a fit-score value: an integer when whole, one decimal otherwise.
     */
    private static function number(float $value): string
    {
        return $value === floor($value) ? (string) (int) $value : number_format($value, 1);
    }

    /**
     * Format a 0–1 rate as a whole-number percentage.
     */
    private static function percent(float $rate): string
    {
        return round($rate * 100).'%';
    }
}
