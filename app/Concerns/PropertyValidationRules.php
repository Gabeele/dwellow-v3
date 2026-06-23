<?php

namespace App\Concerns;

use App\Enums\OccupancyStatus;
use App\Enums\PropertyType;
use App\Enums\RentalType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait PropertyValidationRules
{
    /**
     * Get the validation rules used to validate a property.
     *
     * The rentable detail fields (bedrooms, bathrooms, rent_amount, status) only
     * apply when the property is rented as a whole; for multi-unit properties
     * those details live on each unit, so they are prohibited here.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function propertyRules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'address_line1' => ['required', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'region' => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:20'],
            'country' => ['required', 'string', 'size:2'],
            'type' => ['required', Rule::enum(PropertyType::class)],
            'rental_type' => ['required', Rule::enum(RentalType::class)],
            'bedrooms' => ['prohibited_unless:rental_type,whole', 'nullable', 'integer', 'min:0', 'max:255'],
            'bathrooms' => ['prohibited_unless:rental_type,whole', 'nullable', 'numeric', 'min:0', 'max:99.9'],
            'rent_amount' => ['prohibited_unless:rental_type,whole', 'nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'status' => ['prohibited_unless:rental_type,whole', 'nullable', Rule::enum(OccupancyStatus::class)],
        ];
    }
}
