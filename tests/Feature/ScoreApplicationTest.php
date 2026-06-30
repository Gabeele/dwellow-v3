<?php

use App\Enums\AgentStatus;
use App\Jobs\ScoreApplication;
use App\Models\Agent;
use App\Models\Application;
use App\Screening\ApplicationScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;

uses(RefreshDatabase::class);

it('resolves the scoring service and scores the application', function () {
    $application = Application::factory()->create();

    $this->mock(ApplicationScoringService::class, function (MockInterface $mock) use ($application) {
        $mock->shouldReceive('score')
            ->once()
            ->withArgs(fn (Application $given): bool => $given->is($application))
            ->andReturn(new Agent);
    });

    (new ScoreApplication($application))->handle(app(ApplicationScoringService::class));
});

it('marks a stranded processing agent failed when the job fails', function () {
    $application = Application::factory()->create();
    Agent::factory()->processing()->forApplication($application)->create();

    (new ScoreApplication($application))->failed(new RuntimeException('Ollama timed out'));

    $agent = $application->scoreAgent()->first();

    expect($agent->status)->toBe(AgentStatus::Failed)
        ->and($agent->error)->toBe('Ollama timed out')
        ->and($agent->completed_at)->not->toBeNull();
});

it('leaves a completed agent untouched on a late job failure', function () {
    $application = Application::factory()->create();
    Agent::factory()->completed()->forApplication($application)->create();

    (new ScoreApplication($application))->failed(new RuntimeException('too late'));

    expect($application->scoreAgent()->first()->status)->toBe(AgentStatus::Completed);
});
