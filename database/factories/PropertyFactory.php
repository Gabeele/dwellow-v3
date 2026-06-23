<?php

namespace Database\Factories;

use App\Enums\OccupancyStatus;
use App\Enums\PropertyType;
use App\Enums\RentalType;
use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Property>
 */
class PropertyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'landlord_id' => User::factory()->landlord(),
            'name' => fake()->streetName().' Rental',
            'address_line1' => fake()->streetAddress(),
            'address_line2' => null,
            'city' => fake()->city(),
            'region' => fake()->randomElement(['ON', 'BC', 'AB', 'QC', 'NS']),
            'postal_code' => fake()->postcode(),
            'country' => 'CA',
            'type' => fake()->randomElement(PropertyType::cases()),
            'rental_type' => RentalType::Whole,
            'bedrooms' => fake()->numberBetween(1, 5),
            'bathrooms' => fake()->randomElement([1, 1.5, 2, 2.5, 3]),
            'rent_amount' => fake()->numberBetween(1000, 4000),
            'status' => OccupancyStatus::Available,
        ];
    }

    /**
     * A property rented as a whole (rentable details on the property itself).
     */
    public function whole(): static
    {
        return $this->state(fn (array $attributes) => [
            'rental_type' => RentalType::Whole,
            'bedrooms' => fake()->numberBetween(1, 5),
            'bathrooms' => fake()->randomElement([1, 1.5, 2, 2.5, 3]),
            'rent_amount' => fake()->numberBetween(1000, 4000),
        ]);
    }

    /**
     * A property split into units (rentable details live on each unit).
     */
    public function multiUnit(): static
    {
        return $this->state(fn (array $attributes) => [
            'rental_type' => RentalType::MultiUnit,
            'bedrooms' => null,
            'bathrooms' => null,
            'rent_amount' => null,
        ]);
    }
}
