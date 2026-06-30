<?php

namespace Database\Factories;

use App\Models\Agent;
use App\Models\Application;
use App\Models\Score;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Score>
 */
class ScoreFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'application_id' => Application::factory(),
            'agent_id' => null,
            'fit_score' => $this->faker->numberBetween(40, 95),
            'score_rationale' => $this->faker->sentence(),
            'summary' => $this->faker->paragraph(),
            'red_flags' => [
                'Move-in date is sooner than the unit is available.',
            ],
            'strengths' => [
                'Rent-to-income ratio is comfortable.',
                'References provided.',
            ],
        ];
    }

    /**
     * Attribute the score to the agent run that produced it.
     */
    public function forAgent(Agent $agent): static
    {
        return $this->state(fn (): array => [
            'agent_id' => $agent->getKey(),
        ]);
    }
}
