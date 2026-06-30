<?php

namespace App\Screening;

use App\Models\Application;
use Illuminate\JsonSchema\JsonSchema;

/**
 * Builds the prompt the scoring engine sends to the model for a {@see Score}.
 *
 * The template lives here — not inline in {@see ApplicationScoringService} — so
 * the wording, guardrails, and response contract are tunable in one place and
 * unit-testable without touching the SDK call.
 *
 * The SDK splits a prompt into two parts: the {@see self::instructions()}
 * (system prompt — the stable role, fair-housing rules, unverified-data framing,
 * and the JSON contract) and {@see self::forApplication()} (the per-application
 * body — this applicant's answers plus extracted document text). The response
 * schema is *also* passed to the SDK structured-output mode via {@see self::schema()};
 * describing it in prose here as well is deliberate belt-and-suspenders so weaker
 * local models still honour the contract.
 */
class ScorePrompt
{
    /**
     * Permissible factors the model may weigh — the only things a fair-housing-safe
     * screening aid is allowed to consider.
     *
     * @var list<string>
     */
    private const PERMISSIBLE_FACTORS = [
        'reported income and rent-to-income ratio against the unit rent',
        'employment status, type, and tenure',
        'landlord and other references provided',
        'number of occupants against the unit and any disclosed occupancy limit',
        'completeness, internal consistency, and plausibility of the application',
        'evictions or tenancy issues the applicant has voluntarily disclosed',
    ];

    /**
     * Protected classes (and their proxies) the model must never consider — the
     * hard fair-housing boundary from ADR 0004.
     *
     * @var list<string>
     */
    private const PROTECTED_CLASSES = [
        'race or colour',
        'religion or creed',
        'sex, gender identity, or sexual orientation',
        'national origin, ethnicity, or ancestry',
        'familial status, marital status, pregnancy, or presence of children',
        'disability or medical condition',
        'age',
        'protected source of income (e.g. housing assistance, disability benefits)',
    ];

    /**
     * The system prompt: role, hard fair-housing rules, unverified-data framing,
     * and the JSON response contract the model must return.
     */
    public static function instructions(): string
    {
        $permissible = self::bullets(self::PERMISSIBLE_FACTORS);
        $protected = self::bullets(self::PROTECTED_CLASSES);

        return <<<PROMPT
            You are dwellow's rental-application screening assistant. You produce a
            structured Score that helps a small landlord triage a tenant application
            faster. You are a screening AID, not a decision-maker — dwellow never
            decides for the landlord, and the landlord makes the final call.

            UNVERIFIED DATA
            Everything below is self-reported by the applicant and is NOT verified.
            dwellow runs no credit, bureau, or background checks. Treat documents and
            answers as claims, not proof. Frame the Score and summary accordingly and
            never state a claim as a confirmed fact.

            FAIR-HOUSING SAFETY (HARD REQUIREMENT)
            Consider ONLY these permissible factors:
            {$permissible}
            You must NEVER consider, infer, or reference protected classes or any proxy
            for them, including:
            {$protected}
            Flags must be permissible concerns only — never a protected-class signal.
            If a permissible factor cannot be judged from the information given, say so
            neutrally rather than guessing.

            RESPONSE CONTRACT
            Return a JSON object with exactly these keys:
            - "fit_score": integer 0-100 — overall fit using permissible factors only.
            - "score_rationale": string — one sentence explaining the fit_score.
            - "summary": string — 2-3 neutral sentences summarising the application.
            - "red_flags": array of strings — permissible concerns; empty array if none.
            - "strengths": array of strings — permissible positives; empty array if none.
            PROMPT;
    }

    /**
     * The per-application prompt body: the applicant's labelled answers followed by
     * the extracted document text (capped upstream by the {@see DocumentTextExtractor}).
     */
    public static function forApplication(Application $application, string $documentText = ''): string
    {
        $answers = self::renderAnswers($application);
        $documents = trim($documentText) === ''
            ? 'No document text was provided.'
            : trim($documentText);

        return <<<PROMPT
            Score the following rental application.

            === APPLICATION ANSWERS ===
            {$answers}

            === DOCUMENT TEXT (extracted, unverified) ===
            {$documents}
            PROMPT;
    }

    /**
     * The structured-output schema, mirroring the response contract above. Passed
     * to the SDK's `schema:` argument; the closure receives the JsonSchema factory.
     *
     * @return \Closure(JsonSchema): array<string, mixed>
     */
    public static function schema(): \Closure
    {
        return fn ($schema): array => [
            'fit_score' => $schema->integer()->min(0)->max(100)->description('Overall fit 0-100 using permissible factors only.'),
            'score_rationale' => $schema->string()->description('One sentence explaining the fit_score.'),
            'summary' => $schema->string()->description('2-3 neutral sentences summarising the application.'),
            'red_flags' => $schema->array()->items($schema->string())->description('Permissible concerns; empty if none.'),
            'strengths' => $schema->array()->items($schema->string())->description('Permissible positives; empty if none.'),
        ];
    }

    /**
     * Render the application's answers as "Label: value" lines, using the
     * form snapshot for field labels (same shape the dashboard renders).
     */
    private static function renderAnswers(Application $application): string
    {
        $answers = $application->answers ?? [];
        $lines = [];

        foreach ($application->form_snapshot ?? [] as $field) {
            $key = $field['key'] ?? null;

            if ($key === null) {
                continue;
            }

            $label = $field['label'] ?? $key;
            $lines[] = "{$label}: ".self::formatValue($answers[$key] ?? null);
        }

        return $lines === [] ? 'No answers were provided.' : implode("\n", $lines);
    }

    /**
     * Render a single answer value (scalar, list, or structured reference) as a
     * compact human-readable string.
     */
    private static function formatValue(mixed $value): string
    {
        if (is_array($value)) {
            $parts = [];

            foreach ($value as $key => $item) {
                $parts[] = is_string($key) ? "{$key}: ".self::formatValue($item) : self::formatValue($item);
            }

            return implode(', ', array_filter($parts, fn (string $part): bool => $part !== ''));
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        return (string) ($value ?? '—');
    }

    /**
     * Format a list as indented "- item" bullet lines for the prompt.
     *
     * @param  list<string>  $items
     */
    private static function bullets(array $items): string
    {
        return implode("\n", array_map(fn (string $item): string => "- {$item}", $items));
    }
}
