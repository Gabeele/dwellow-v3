<?php

use App\Enums\AgentStatus;
use App\Enums\AgentType;
use App\Enums\ApplicationStatus;
use App\Models\Agent;
use App\Models\Application;
use App\Models\ApplicationLink;
use App\Models\Score;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function attachScoreAgent(Application $application): Agent
{
    $agent = new Agent([
        'type' => AgentType::Score,
        'status' => AgentStatus::Completed,
    ]);

    $agent->analyzable()->associate($application);
    $agent->save();

    return $agent;
}

it('persists with array casts intact', function () {
    $application = Application::factory()->create();

    $fresh = $application->fresh();

    expect($fresh->answers)->toBeArray()
        ->and($fresh->form_snapshot)->toBeArray()
        ->and($fresh->form_snapshot)->not->toBeEmpty()
        ->and($fresh->status)->toBe(ApplicationStatus::New)
        ->and($fresh->submitted_at)->not->toBeNull();
});

it('belongs to its link and unit', function () {
    $application = Application::factory()->create();

    expect($application->applicationLink)->toBeInstanceOf(ApplicationLink::class)
        ->and($application->unit)->toBeInstanceOf(Unit::class)
        ->and($application->unit_id)->toBe($application->applicationLink->unit_id);
});

it('is included in a unit\'s applications', function () {
    $unit = Unit::factory()->create();
    $link = ApplicationLink::factory()->create(['unit_id' => $unit->id]);
    $application = Application::factory()->create([
        'application_link_id' => $link->id,
        'unit_id' => $unit->id,
    ]);

    expect($unit->applications()->pluck('id'))->toContain($application->id);
});

it('resolves its score agent and score relationships', function () {
    $application = Application::factory()->create();
    $agent = attachScoreAgent($application);

    $score = new Score(['fit_score' => 72]);
    $score->application()->associate($application);
    $score->agent()->associate($agent);
    $score->save();

    expect($application->scoreAgent)->toBeInstanceOf(Agent::class)
        ->and($application->scoreAgent->is($agent))->toBeTrue()
        ->and($application->agents)->toHaveCount(1)
        ->and($application->score)->toBeInstanceOf(Score::class)
        ->and($application->score->is($score))->toBeTrue();
});

it('only treats score-type agents as its score agent', function () {
    $application = Application::factory()->create();

    expect($application->scoreAgent)->toBeNull();

    attachScoreAgent($application);

    expect($application->fresh()->scoreAgent)->not->toBeNull();
});

it('exposes a polymorphic agent label and result url', function () {
    $application = Application::factory()->create([
        'applicant_first_name' => 'Jane',
        'applicant_last_name' => 'Doe',
    ]);

    expect($application->agentLabel())->toBe('Score — Application: Jane Doe')
        ->and($application->agentUrl())->toBe(route('applicants.show', $application));
});
