<?php

use App\Enums\AgentStatus;
use App\Enums\AgentType;
use App\Models\Agent;
use App\Models\Application;
use App\Models\Score;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('builds a valid score agent attached to an application', function () {
    $agent = Agent::factory()->create();

    expect($agent->type)->toBe(AgentType::Score)
        ->and($agent->status)->toBe(AgentStatus::Pending)
        ->and($agent->analyzable)->toBeInstanceOf(Application::class);
});

it('builds an agent for a specific application via forApplication', function () {
    $application = Application::factory()->create();

    $agent = Agent::factory()->forApplication($application)->create();

    expect($agent->analyzable->is($application))->toBeTrue();
});

it('reflects each lifecycle state', function () {
    expect(Agent::factory()->pending()->create())
        ->status->toBe(AgentStatus::Pending)
        ->completed_at->toBeNull();

    expect(Agent::factory()->processing()->create())
        ->status->toBe(AgentStatus::Processing)
        ->started_at->not->toBeNull()
        ->completed_at->toBeNull();

    expect(Agent::factory()->completed()->create())
        ->status->toBe(AgentStatus::Completed)
        ->completed_at->not->toBeNull()
        ->usage->toBeArray();

    expect(Agent::factory()->failed()->create())
        ->status->toBe(AgentStatus::Failed)
        ->error->not->toBeNull();
});

it('builds a valid score with array flags and strengths', function () {
    $score = Score::factory()->create();

    expect($score->application)->toBeInstanceOf(Application::class)
        ->and($score->fit_score)->toBeInt()
        ->and($score->red_flags)->toBeArray()
        ->and($score->strengths)->toBeArray()
        ->and($score->agent_id)->toBeNull();
});

it('attributes a score to its agent via forAgent', function () {
    $application = Application::factory()->create();
    $agent = Agent::factory()->forApplication($application)->completed()->create();

    $score = Score::factory()->for($application)->forAgent($agent)->create();

    expect($score->agent->is($agent))->toBeTrue()
        ->and($score->application->is($application))->toBeTrue();
});
