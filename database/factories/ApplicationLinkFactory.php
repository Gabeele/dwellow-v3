<?php

namespace Database\Factories;

use App\Models\ApplicationLink;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApplicationLink>
 */
class ApplicationLinkFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'unit_id' => Unit::factory(),
            'label' => $this->faker->optional()->words(2, true),
            'is_accepting' => true,
            'expires_at' => null,
            'revoked_at' => null,
        ];
    }

    /**
     * The link has been revoked by the landlord.
     */
    public function revoked(): static
    {
        return $this->state(fn (array $attributes): array => [
            'revoked_at' => now(),
        ]);
    }

    /**
     * The link has passed its expiry date.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'expires_at' => now()->subDay(),
        ]);
    }

    /**
     * The link is no longer accepting submissions.
     */
    public function notAccepting(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_accepting' => false,
        ]);
    }
}
