<?php

namespace App\Jobs;

use App\Enums\AgentStatus;
use App\Models\Application;
use App\Screening\AgentHandler;
use App\Screening\ApplicationScoringService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Runs the score {@see AgentHandler} for an {@see Application} off the
 * request cycle. A retry mutates the application's single score Agent in place (1:1),
 * so a re-run never leaves a duplicate row behind.
 */
class ScoreApplication implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 2;

    /**
     * The number of seconds to wait before retrying each attempt.
     *
     * @var list<int>
     */
    public array $backoff = [10, 30];

    /**
     * The number of seconds the job may run before timing out (local Ollama is slow).
     */
    public int $timeout = 120;

    public function __construct(
        public readonly Application $application,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(ApplicationScoringService $scoringService): void
    {
        $scoringService->score($this->application);
    }

    /**
     * Handle a job failure: mark the run's Agent failed so a dead job never strands a
     * row in `processing`.
     */
    public function failed(?Throwable $exception): void
    {
        $agent = $this->application->scoreAgent()->first();

        if ($agent === null || $agent->status === AgentStatus::Completed) {
            return;
        }

        $agent->status = AgentStatus::Failed;
        $agent->error = $exception?->getMessage();
        $agent->completed_at = now();
        $agent->save();
    }
}
