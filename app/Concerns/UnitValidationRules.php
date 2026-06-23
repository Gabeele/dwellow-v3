<?php

namespace App\Concerns;

use App\Enums\OccupancyStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait UnitValidationRules
{
    /**
     * Get the validation rules used to validate a unit.
     *
     * Unit labels must be unique within their parent property.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function unitRules(int $propertyId, ?int $unitId = null): array
    {
        return [
            'label' => [
                'required',
                'string',
                'max:255',
                Rule::unique('units', 'label')
                    ->where('property_id', $propertyId)
                    ->ignore($unitId),
            ],
            'bedrooms' => ['nullable', 'integer', 'min:0', 'max:255'],
            'bathrooms' => ['nullable', 'numeric', 'min:0', 'max:99.9'],
            'rent_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'status' => ['required', Rule::enum(OccupancyStatus::class)],
        ];
    }
}
