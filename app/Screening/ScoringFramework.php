<?php

namespace App\Screening;

use App\Enums\CriterionAssessment;

/**
 * The fixed rental-screening scoring framework: the eight landlord criteria
 * every application is assessed on, in a stable order.
 *
 * This is the single source of truth for the rubric. The prompt asks the model
 * to grade exactly these criteria ({@see ScorePrompt}), the validator enforces
 * that all eight are present with a valid {@see CriterionAssessment}
 * ({@see ScoreResponseValidator}), and the UI renders them in this order. Scoring
 * the same axes every time is what makes Scores consistent and comparable across
 * applications — the model grades the rubric, it does not choose the rubric.
 *
 * Criteria stay within the fair-housing-permissible factors (ADR 0004): they
 * judge affordability, reliability, track record, fit, and trustworthiness of the
 * application — never a protected class or any proxy for one.
 */
class ScoringFramework
{
    /**
     * The criteria, keyed by their stable identifier, each with a display label
     * and the landlord-lens guidance the model uses to grade it.
     *
     * @var array<string, array{label: string, guidance: string}>
     */
    private const CRITERIA = [
        'affordability' => [
            'label' => 'Affordability',
            'guidance' => 'Rent-to-income: the documented/stated gross income against the unit rent. Strong when rent is comfortably within reach (roughly ≤30% of gross), Weak when stretched or unaffordable.',
        ],
        'employment' => [
            'label' => 'Employment',
            'guidance' => 'Stability and reliability of income: employment type and tenure. Strong for stable full-time work with solid tenure; Weak for irregular income, recent employment gaps, very short tenure, or no steady employer. Grade income stability only — never the source of income itself.',
        ],
        'credit' => [
            'label' => 'Credit',
            'guidance' => 'Credit standing from the self-reported range and any credit report (score, utilisation, derogatory marks). Strong for good/clean credit, Weak for poor or derogatory.',
        ],
        'references' => [
            'label' => 'References',
            'guidance' => 'Landlord or other references provided and contactable. Unverified when none are provided; never penalise beyond the missing information itself.',
        ],
        'rental_history' => [
            'label' => 'Rental history',
            'guidance' => 'Prior tenancy: time at current address, reason for leaving, and any disclosed evictions or tenancy issues. Weak when an eviction or problematic history is disclosed.',
        ],
        'occupancy' => [
            'label' => 'Occupancy',
            'guidance' => 'Household fit: number of occupants against the unit bedrooms and any disclosed limit. Weak when the unit is over-occupied for its size.',
        ],
        'identity' => [
            'label' => 'Identity',
            'guidance' => 'Document consistency: do the photo ID, pay stub, and credit report match the stated name, date of birth, and income? Weak on mismatches, Unverified when ID is missing or unreadable.',
        ],
        'disclosures' => [
            'label' => 'Disclosures',
            'guidance' => 'Self-disclosed tenancy factors such as pets and smoking, weighed against the unit. Adequate when standard, Weak when they conflict with the unit or its rules.',
        ],
    ];

    /**
     * The criterion keys, in canonical order.
     *
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::CRITERIA);
    }

    /**
     * The display label for a criterion key, or the key itself if unknown.
     */
    public static function label(string $key): string
    {
        return self::CRITERIA[$key]['label'] ?? $key;
    }

    /**
     * The criteria as "- key (Label): guidance" lines for the prompt.
     */
    public static function promptLines(): string
    {
        $lines = [];

        foreach (self::CRITERIA as $key => $criterion) {
            $lines[] = "- {$key} ({$criterion['label']}): {$criterion['guidance']}";
        }

        return implode("\n", $lines);
    }
}
