<?php

namespace App\Screening;

use App\Enums\CriterionAssessment;

/**
 * Validates a decoded model response against the {@see Score} contract,
 * independent of the SDK's structured-output mode.
 *
 * The structured-output schema is the first line of defence, but local models
 * honour it imperfectly — this validator is the belt to the schema's braces. It
 * returns a {@see ScoreValidationResult}: the clean, typed payload on success or
 * the list of contract violations on failure, so the scoring service can decide
 * between persisting a Score and running its single repair retry.
 *
 * It deliberately depends on nothing but PHP — no container, no SDK — so it can
 * guard a raw decoded payload from any source.
 */
class ScoreResponseValidator
{
    /**
     * Validate a decoded model response against the Score response contract.
     *
     * On success the returned value contains exactly the five contract keys with
     * normalised types; any extra keys the model emitted are stripped.
     *
     * @param  mixed  $payload  The decoded structured payload (expected: an associative array).
     */
    public function validate(mixed $payload): ScoreValidationResult
    {
        if (! is_array($payload)) {
            return ScoreValidationResult::invalid(['The response must be a JSON object.']);
        }

        $errors = [];

        if (! array_key_exists('fit_score', $payload)) {
            $errors[] = 'The fit_score field is required.';
        } elseif (! is_int($payload['fit_score'])) {
            $errors[] = 'The fit_score field must be an integer.';
        } elseif ($payload['fit_score'] < 0 || $payload['fit_score'] > 100) {
            $errors[] = 'The fit_score field must be between 0 and 100.';
        }

        foreach (['score_rationale', 'summary'] as $key) {
            if (! array_key_exists($key, $payload)) {
                $errors[] = "The {$key} field is required.";
            } elseif (! is_string($payload[$key])) {
                $errors[] = "The {$key} field must be a string.";
            }
        }

        foreach (['red_flags', 'strengths'] as $key) {
            if (! array_key_exists($key, $payload)) {
                $errors[] = "The {$key} field is required.";
            } elseif (! self::isListOfStrings($payload[$key])) {
                $errors[] = "The {$key} field must be an array of strings.";
            }
        }

        $rubric = null;

        if (! array_key_exists('rubric', $payload)) {
            $errors[] = 'The rubric field is required.';
        } else {
            $rubric = self::normaliseRubric($payload['rubric'], $errors);
        }

        if ($errors !== []) {
            return ScoreValidationResult::invalid($errors);
        }

        return ScoreValidationResult::valid([
            'fit_score' => $payload['fit_score'],
            'score_rationale' => $payload['score_rationale'],
            'summary' => $payload['summary'],
            'rubric' => $rubric,
            'red_flags' => array_values($payload['red_flags']),
            'strengths' => array_values($payload['strengths']),
        ]);
    }

    /**
     * Validate and normalise the rubric against the fixed scoring framework.
     *
     * Enforces consistency: the rubric must grade every framework criterion
     * exactly once with a valid {@see CriterionAssessment}. On success
     * it returns the rows in canonical framework order — each reduced to
     * criterion/assessment/note, assessment lower-cased, note coerced to a string —
     * so a Score always exposes the same eight axes. On failure it appends the
     * single descriptive {@see self::rubricError()} and returns null.
     *
     * @param  list<string>  $errors
     * @return list<array{criterion: string, assessment: string, note: string}>|null
     */
    private static function normaliseRubric(mixed $value, array &$errors): ?array
    {
        $criteria = ScoringFramework::keys();
        $assessments = CriterionAssessment::values();

        if (! is_array($value) || ! array_is_list($value)) {
            $errors[] = self::rubricError();

            return null;
        }

        $byCriterion = [];

        foreach ($value as $item) {
            if (! is_array($item)
                || ! isset($item['criterion'], $item['assessment'])
                || ! is_string($item['criterion'])
                || ! is_string($item['assessment'])) {
                $errors[] = self::rubricError();

                return null;
            }

            $criterion = strtolower(trim($item['criterion']));
            $assessment = strtolower(trim($item['assessment']));

            if (! in_array($criterion, $criteria, true) || ! in_array($assessment, $assessments, true)) {
                $errors[] = self::rubricError();

                return null;
            }

            $note = isset($item['note']) && is_string($item['note']) ? trim($item['note']) : '';
            $byCriterion[$criterion] = compact('criterion', 'assessment', 'note');
        }

        // Every framework criterion must be graded exactly once.
        if (count($byCriterion) !== count($criteria)) {
            $errors[] = self::rubricError();

            return null;
        }

        // Re-emit in canonical framework order so the rubric is always consistent.
        return array_map(fn (string $key): array => $byCriterion[$key], $criteria);
    }

    /**
     * The single message used for any malformed rubric payload, naming the exact
     * shape and the framework so the repair retry can correct it.
     */
    private static function rubricError(): string
    {
        $criteria = implode(', ', ScoringFramework::keys());
        $assessments = implode(', ', CriterionAssessment::values());

        return 'The rubric field must be an array grading every criterion exactly once '
            ."({$criteria}), each an object with a criterion, an assessment of {$assessments}, and a short note.";
    }

    /**
     * Whether the value is an array containing only string items (an empty array
     * qualifies — "no flags" is a valid Score).
     */
    private static function isListOfStrings(mixed $value): bool
    {
        if (! is_array($value)) {
            return false;
        }

        foreach ($value as $item) {
            if (! is_string($item)) {
                return false;
            }
        }

        return true;
    }
}
