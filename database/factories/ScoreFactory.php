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
            'rubric' => [
                ['criterion' => 'affordability', 'assessment' => 'strong', 'note' => '~28% of gross income'],
                ['criterion' => 'employment', 'assessment' => 'strong', 'note' => '3 years, full-time'],
                ['criterion' => 'credit', 'assessment' => 'adequate', 'note' => 'good, some utilisation'],
                ['criterion' => 'references', 'assessment' => 'unverified', 'note' => 'none provided'],
                ['criterion' => 'rental_history', 'assessment' => 'adequate', 'note' => 'no issues disclosed'],
                ['criterion' => 'occupancy', 'assessment' => 'strong', 'note' => '2 in a 3-bed'],
                ['criterion' => 'identity', 'assessment' => 'strong', 'note' => 'ID matches answers'],
                ['criterion' => 'disclosures', 'assessment' => 'adequate', 'note' => 'no pets, non-smoker'],
            ],
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
