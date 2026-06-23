<?php

namespace Database\Factories;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\ApplicationLink;
use App\Screening\DefaultApplicationForm;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Application>
 */
class ApplicationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $firstName = $this->faker->firstName();
        $lastName = $this->faker->lastName();
        $email = $this->faker->safeEmail();
        $phone = $this->faker->phoneNumber();

        return [
            'application_link_id' => ApplicationLink::factory(),
            'unit_id' => fn (array $attributes): int => ApplicationLink::findOrFail($attributes['application_link_id'])->unit_id,
            'applicant_first_name' => $firstName,
            'applicant_last_name' => $lastName,
            'applicant_email' => $email,
            'applicant_phone' => $phone,
            'answers' => [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'phone' => $phone,
            ],
            'form_snapshot' => collect(DefaultApplicationForm::sections())
                ->flatMap(fn (array $section): array => $section['fields'])
                ->values()
                ->all(),
            'status' => ApplicationStatus::New,
            'submitted_at' => now(),
        ];
    }
}
