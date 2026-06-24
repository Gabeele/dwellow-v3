<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\Document;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
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
            'application_id' => Application::factory(),
            'field_key' => $this->faker->randomElement(['photo_id', 'pay_stubs', 'proof_of_income']),
            'disk' => 'local',
            'path' => 'applications/'.$this->faker->uuid().'/'.$originalName,
            'original_name' => $originalName,
            'mime_type' => 'application/pdf',
            'size' => $this->faker->numberBetween(1024, 5_000_000),
        ];
    }
}
