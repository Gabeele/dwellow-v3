<?php

namespace Database\Factories;

use App\Models\ApplicationDraft;
use App\Models\ApplicationDraftDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApplicationDraftDocument>
 */
class ApplicationDraftDocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $originalName = $this->faker->word().'.pdf';

        return [
            'application_draft_id' => ApplicationDraft::factory(),
            'field_key' => $this->faker->randomElement(['photo_id', 'pay_stubs', 'proof_of_income']),
            'disk' => 'local',
            'path' => 'application-drafts/'.$this->faker->uuid().'/'.$originalName,
            'original_name' => $originalName,
            'mime_type' => 'application/pdf',
            'size' => $this->faker->numberBetween(1024, 5_000_000),
        ];
    }
}
