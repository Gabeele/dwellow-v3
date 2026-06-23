<?php

namespace Database\Factories;

use App\Models\SentEmail;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SentEmail>
 */
class SentEmailFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'mailer' => 'smtp',
            'subject' => fake()->sentence(),
            'from' => 'hello@dwellow.app',
            'to' => [fake()->unique()->safeEmail()],
            'cc' => null,
            'bcc' => null,
            'body' => '<p>'.fake()->paragraph().'</p>',
            'sent_at' => now(),
        ];
    }
}
