<?php

namespace Database\Factories;

use App\Models\ApplicationForm;
use App\Models\Unit;
use App\Screening\DefaultApplicationForm;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApplicationForm>
 */
class ApplicationFormFactory extends Factory
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
            'sections' => DefaultApplicationForm::sections(),
        ];
    }

    /**
     * Attach the form to a specific unit.
     */
    public function forUnit(Unit $unit): static
    {
        return $this->state(fn (array $attributes): array => [
            'unit_id' => $unit->id,
        ]);
    }
}
