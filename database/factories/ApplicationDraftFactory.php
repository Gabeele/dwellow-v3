<?php

namespace Database\Factories;

use App\Models\ApplicationDraft;
use App\Models\ApplicationLink;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApplicationDraft>
 */
class ApplicationDraftFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'application_link_id' => ApplicationLink::factory(),
            'answers' => [],
            'current_step' => 0,
        ];
    }
}
