<?php

use App\Enums\AgentStatus;
use App\Enums\AgentType;
use App\Models\Agent;
use App\Models\Application;
use App\Models\Score;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeScoreAgent(Application $application): Agent
{
    $agent = new Agent([
        'type' => AgentType::Score,
        'status' => AgentStatus::Completed,
    ]);

    $agent->analyzable()->associate($application);
    $agent->save();

    return $agent;
}

function makeScore(Application $application, ?Agent $agent = null, array $attributes = []): Score
{
    $score = new Score(array_merge([
        'fit_score' => 80,
        'score_rationale' => 'Strong income and references.',
        'summary' => 'The applicant meets the unit criteria with verifiable references.',
        'red_flags' => ['Move-in date is sooner than the unit is available.'],
        'strengths' => ['Rent-to-income ratio is comfortable.'],
    ], $attributes));

    $score->application()->associate($application);

    if ($agent !== null) {
        $score->agent()->associate($agent);
    }

    $score->save();

    return $score;
}

it('belongs to an application and the agent that produced it', function () {
    $application = Application::factory()->create();
    $agent = makeScoreAgent($application);

    $score = makeScore($application, $agent);

    expect($score->application)->toBeInstanceOf(Application::class)
        ->and($score->application->is($application))->toBeTrue()
        ->and($score->agent)->toBeInstanceOf(Agent::class)
        ->and($score->agent->is($agent))->toBeTrue();
});

it('casts the flag and strength columns to arrays', function () {
    $application = Application::factory()->create();

    $score = makeScore($application, makeScoreAgent($application), [
        'red_flags' => ['Eviction disclosed in 2022.'],
        'strengths' => ['Two years at current employer.', 'References provided.'],
    ]);

    $fresh = $score->fresh();

    expect($fresh->fit_score)->toBe(80)
        ->and($fresh->red_flags)->toBe(['Eviction disclosed in 2022.'])
        ->and($fresh->strengths)->toBe(['Two years at current employer.', 'References provided.']);
});

it('enforces one score per application', function () {
    $application = Application::factory()->create();

    makeScore($application);

    expect(fn () => makeScore($application))->toThrow(QueryException::class);
});

it('nulls the agent reference when the agent is deleted', function () {
    $application = Application::factory()->create();
    $agent = makeScoreAgent($application);

    $score = makeScore($application, $agent);

    $agent->delete();

    expect($score->fresh()->agent_id)->toBeNull();
});
