<?php

use App\Enums\AgentStatus;
use App\Enums\AgentType;
use App\Models\Agent;
use App\Models\Application;
use Carbon\CarbonInterface;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeAgent(Application $application, array $attributes = []): Agent
{
    $agent = new Agent(array_merge([
        'type' => AgentType::Score,
        'status' => AgentStatus::Pending,
    ], $attributes));

    $agent->analyzable()->associate($application);
    $agent->save();

    return $agent;
}

it('belongs to a polymorphic analyzable subject', function () {
    $application = Application::factory()->create();

    $agent = makeAgent($application);

    expect($agent->analyzable)->toBeInstanceOf(Application::class)
        ->and($agent->analyzable->is($application))->toBeTrue();
});

it('casts enums, json columns, and timestamps', function () {
    $application = Application::factory()->create();

    $agent = makeAgent($application, [
        'provider' => 'ollama',
        'model' => 'qwen2.5:14b-instruct',
        'status' => AgentStatus::Completed,
        'raw_response' => ['fit_score' => 80],
        'usage' => ['input_tokens' => 100],
        'started_at' => now()->subMinute(),
        'completed_at' => now(),
    ]);

    $fresh = $agent->fresh();

    expect($fresh->type)->toBe(AgentType::Score)
        ->and($fresh->status)->toBe(AgentStatus::Completed)
        ->and($fresh->raw_response)->toBe(['fit_score' => 80])
        ->and($fresh->usage)->toBe(['input_tokens' => 100])
        ->and($fresh->started_at)->toBeInstanceOf(CarbonInterface::class)
        ->and($fresh->completed_at)->toBeInstanceOf(CarbonInterface::class);
});

it('enforces one agent per type per subject', function () {
    $application = Application::factory()->create();

    makeAgent($application);

    expect(fn () => makeAgent($application))->toThrow(QueryException::class);
});

it('allows a different subject to have its own score agent', function () {
    $first = Application::factory()->create();
    $second = Application::factory()->create();

    makeAgent($first);

    expect(fn () => makeAgent($second))->not->toThrow(QueryException::class);
});
