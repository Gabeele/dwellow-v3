<?php

namespace App\Screening;

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

        if ($errors !== []) {
            return ScoreValidationResult::invalid($errors);
        }

        return ScoreValidationResult::valid([
            'fit_score' => $payload['fit_score'],
            'score_rationale' => $payload['score_rationale'],
            'summary' => $payload['summary'],
            'red_flags' => array_values($payload['red_flags']),
            'strengths' => array_values($payload['strengths']),
        ]);
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
