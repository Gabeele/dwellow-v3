<?php

namespace App\Http\Resources;

use App\Models\Application;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The applicant-row shape shared by the landlord's applications tables (portfolio-wide
 * and per-property). `property_name` is only emitted when the property relation is
 * loaded, so the per-property page — which already knows its property — omits it.
 *
 * @mixin Application
 */
class ApplicationRowResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'applicant_name' => trim("{$this->applicant_first_name} {$this->applicant_last_name}"),
            'applicant_email' => $this->applicant_email,
            'unit_label' => $this->unit->label,
            'submitted_at' => $this->submitted_at,
            'status' => $this->status,
            'documents_count' => $this->documents_count,
            'score' => $this->scoreRow(),
            'url' => route('applicants.show', $this->resource),
            ...($this->unit->relationLoaded('property') ? [
                'property_name' => $this->unit->property->name ?? $this->unit->property->address_line1,
            ] : []),
        ];
    }

    /**
     * The compact Score for the row's hoverable fit-score badge, or null while no
     * Score has been produced. Only the headline number, rationale, and rubric the
     * table tooltip renders are exposed — not the full summary/flags payload.
     *
     * @return array{fit_score: int|null, score_rationale: string|null, rubric: array<int, array{criterion: string, assessment: string, note: string}>}|null
     */
    private function scoreRow(): ?array
    {
        $score = $this->relationLoaded('score') ? $this->score : null;

        if ($score === null) {
            return null;
        }

        return [
            'fit_score' => $score->fit_score,
            'score_rationale' => $score->score_rationale,
            'rubric' => $score->rubric ?? [],
        ];
    }
}
