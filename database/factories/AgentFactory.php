<?php

namespace Database\Factories;

use App\Enums\AgentStatus;
use App\Enums\AgentType;
use App\Models\Agent;
use App\Models\Application;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Agent>
 */
class AgentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'analyzable_type' => (new Application)->getMorphClass(),
            'analyzable_id' => Application::factory(),
            'type' => AgentType::Score,
            'provider' => 'ollama',
            'model' => 'qwen2.5:14b-instruct',
            'status' => AgentStatus::Pending,
            'raw_response' => null,
            'usage' => null,
            'error' => null,
            'started_at' => null,
            'completed_at' => null,
        ];
    }

    /**
     * The agent run is queued and has not started.
     */
    public function pending(): static
    {
        return $this->state(fn (): array => [
            'status' => AgentStatus::Pending,
            'started_at' => null,
            'completed_at' => null,
            'error' => null,
        ]);
    }

    /**
     * The agent run is in progress.
     */
    public function processing(): static
    {
        return $this->state(fn (): array => [
            'status' => AgentStatus::Processing,
            'started_at' => now(),
            'completed_at' => null,
            'error' => null,
        ]);
    }

    /**
     * The agent run finished successfully.
     */
    public function completed(): static
    {
        return $this->state(fn (): array => [
            'status' => AgentStatus::Completed,
            'started_at' => now()->subMinute(),
            'completed_at' => now(),
            'usage' => ['input_tokens' => 1200, 'output_tokens' => 180],
            'error' => null,
        ]);
    }

    /**
     * The agent run failed after exhausting its repair retry.
     */
    public function failed(): static
    {
        return $this->state(fn (): array => [
            'status' => AgentStatus::Failed,
            'started_at' => now()->subMinute(),
            'completed_at' => now(),
            'error' => 'Structured output validation failed after repair retry.',
        ]);
    }

    /**
     * Attach the agent to a specific application as its analyzable subject.
     */
    public function forApplication(Application $application): static
    {
        return $this->state(fn (): array => [
            'analyzable_type' => $application->getMorphClass(),
            'analyzable_id' => $application->getKey(),
        ]);
    }
}
