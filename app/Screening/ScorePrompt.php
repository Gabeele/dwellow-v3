<?php

namespace App\Screening;

use App\Enums\CriterionAssessment;
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
        $criteria = ScoringFramework::promptLines();
        $criterionKeys = implode(', ', ScoringFramework::keys());
        $assessments = implode(', ', CriterionAssessment::values());

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

            CROSS-REFERENCE THE EVIDENCE (this is how you grade the rubric)
            Read the documents and reconcile them against the applicant's stated
            answers and against the unit before grading. In particular:
            - Identity: does the name/date of birth on the photo ID match the answers?
            - Income: does the pay stub / employment letter corroborate the stated
              gross income? Compute rent-to-income against the unit's rent.
            - Credit: does any credit report match the self-reported credit range?
            - Occupancy: compare the number of occupants to the unit's bedroom count.
            - Note material disclosures (pets, smoking, prior evictions).
            Do the arithmetic before asserting a mismatch — e.g. a monthly figure
            times twelve should match an annual one; do not invent contradictions.

            SCORING FRAMEWORK (grade every criterion, the same way every time)
            Grade EACH of these eight criteria — always all eight, in this order:
            {$criteria}
            Give each criterion exactly one assessment from this fixed scale:
            - "strong"     — clearly supports the tenancy
            - "adequate"   — acceptable, no real concern
            - "weak"       — a concern that counts against the application
            - "unverified" — missing or unreadable information; judged neither way

            RESPONSE CONTRACT
            Return a JSON object with exactly these keys:
            - "fit_score": integer 0-100 — overall fit, CONSISTENT with the rubric.
              Anchor it to how many criteria you graded "weak": none → 75-95;
              one or two → 45-70; three or more → 8-40. "unverified" is
              cautionary, not a penalty. Use permissible factors only.
            - "score_rationale": string — ONE short sentence on why the score is what it is.
            - "summary": string — 2-4 neutral sentences ANALYSING the application
              (matches, mismatches, rent-to-income, occupancy, notable disclosures) —
              not a restatement of the answers.
            - "rubric": array of EXACTLY the eight criteria above, in the same order.
              Each object has:
                - "criterion": one of: {$criterionKeys}.
                - "assessment": one of: {$assessments}.
                - "note": a very short phrase (≤ 8 words) giving the concrete reason,
                  e.g. "~24% of gross" or "no references provided". Do NOT restate the
                  red_flags here — this is the calculation, not the concerns list.
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
        $unit = self::renderUnit($application);
        $answers = self::renderAnswers($application);
        $documents = trim($documentText) === ''
            ? 'No document text was provided.'
            : trim($documentText);

        return <<<PROMPT
            Score the following rental application.

            === UNIT (what the applicant is applying for) ===
            {$unit}

            === APPLICATION ANSWERS ===
            {$answers}

            === DOCUMENT TEXT (extracted, unverified) ===
            {$documents}
            PROMPT;
    }

    /**
     * Render the applied-for unit so the model can judge rent-to-income and
     * occupancy against the actual unit, not in a vacuum.
     */
    private static function renderUnit(Application $application): string
    {
        // Read an already-loaded relation directly; only touch the database when
        // the application is persisted (it always is during real scoring) so the
        // prompt builder stays usable on in-memory models in unit tests.
        if ($application->relationLoaded('unit')) {
            $unit = $application->unit;
        } elseif ($application->exists) {
            $unit = $application->loadMissing('unit')->unit;
        } else {
            $unit = null;
        }

        if ($unit === null) {
            return 'No unit details were provided.';
        }

        $bedrooms = $unit->bedrooms === null ? 'not specified' : (string) $unit->bedrooms;
        $bathrooms = $unit->bathrooms === null ? 'not specified' : (string) $unit->bathrooms;
        $rent = $unit->rent_amount === null
            ? 'not specified'
            : '$'.number_format((float) $unit->rent_amount, 2).' / month';

        return implode("\n", [
            'Unit: '.($unit->label ?? '—'),
            "Bedrooms: {$bedrooms}",
            "Bathrooms: {$bathrooms}",
            "Monthly rent: {$rent}",
        ]);
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
            'fit_score' => $schema->integer()->min(0)->max(100)->description('Overall fit 0-100, consistent with the rubric.'),
            'score_rationale' => $schema->string()->description('One short sentence on why the score is what it is.'),
            'summary' => $schema->string()->description('2-4 neutral sentences analysing the application (matches, mismatches, rent-to-income, occupancy, disclosures).'),
            'rubric' => $schema->array()->items($schema->object([
                'criterion' => $schema->string()->enum(ScoringFramework::keys())->description('The scoring-framework criterion.'),
                'assessment' => $schema->string()->enum(CriterionAssessment::values())->description('The verdict for this criterion.'),
                'note' => $schema->string()->description('A very short phrase (≤ 8 words) giving the concrete reason.'),
            ]))->description('Exactly the eight framework criteria, in order, each graded.'),
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
