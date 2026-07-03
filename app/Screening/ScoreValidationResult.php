<?php

namespace App\Screening;

/**
 * The outcome of checking a model response against the Score contract — either
 * the clean, typed payload or the list of contract violations.
 *
 * Modelled as a result object (not an exception) because an invalid payload is
 * an *expected* branch in {@see ApplicationScoringService}: it triggers the
 * single repair retry rather than an error path.
 */
class ScoreValidationResult
{
    /**
     * @param  array{fit_score: int, score_rationale: string, summary: string, red_flags: list<string>, strengths: list<string>}|null  $value  Clean payload when valid; null otherwise.
     * @param  list<string>  $errors  Human-readable violations when invalid; empty otherwise.
     */
    private function __construct(
        public readonly bool $valid,
        public readonly ?array $value,
        public readonly array $errors,
    ) {}

    /**
     * @param  array{fit_score: int, score_rationale: string, summary: string, red_flags: list<string>, strengths: list<string>}  $value
     */
    public static function valid(array $value): self
    {
        return new self(true, $value, []);
    }

    /**
     * @param  list<string>  $errors
     */
    public static function invalid(array $errors): self
    {
        return new self(false, null, $errors);
    }
}
