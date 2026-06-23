<?php

namespace Database\Factories;

use App\Enums\OccupancyStatus;
use App\Models\Property;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Unit>
 */
class UnitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'property_id' => Property::factory()->multiUnit(),
            'label' => 'Unit '.fake()->unique()->numerify('###'),
            'bedrooms' => fake()->numberBetween(0, 4),
            'bathrooms' => fake()->randomElement([1, 1.5, 2]),
            'rent_amount' => fake()->numberBetween(800, 3000),
            'status' => OccupancyStatus::Available,
        ];
    }
}
