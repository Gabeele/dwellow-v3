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
            'url' => route('applicants.show', $this->resource),
            ...($this->unit->relationLoaded('property') ? [
                'property_name' => $this->unit->property->name ?? $this->unit->property->address_line1,
            ] : []),
        ];
    }
}
