<?php

use App\Enums\AgentStatus;
use App\Enums\AgentType;
use App\Models\Application;
use App\Models\Score;
use App\Screening\Agents\ScoreAgent;
use App\Screening\ApplicationScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * A valid Score payload matching the locked response contract.
 *
 * @return array{fit_score: int, score_rationale: string, summary: string, red_flags: list<string>, strengths: list<string>}
 */
function completedScorePayload(): array
{
    return [
        'fit_score' => 82,
        'score_rationale' => 'Stable income comfortably covers the rent.',
        'summary' => 'The applicant reports steady employment and references. Income is well above the rent-to-income threshold. The application is complete and consistent.',
        'red_flags' => ['Requested move-in date is before the unit is available.'],
        'strengths' => ['Rent-to-income ratio under 30%', 'Two contactable references provided'],
    ];
}

it('completes the agent and persists the Score on the happy path', function () {
    ScoreAgent::fake([completedScorePayload()]);

    $application = Application::factory()->create();

    $agent = app(ApplicationScoringService::class)->run($application);

    expect($agent->status)->toBe(AgentStatus::Completed)
        ->and($agent->type)->toBe(AgentType::Score)
        ->and($agent->model)->not->toBeNull()
        ->and($agent->usage)->toBeArray()
        ->and($agent->started_at)->not->toBeNull()
        ->and($agent->completed_at)->not->toBeNull()
        ->and($agent->error)->toBeNull();

    $score = $application->refresh()->score;

    expect($score)->not->toBeNull()
        ->and($score->fit_score)->toBe(82)
        ->and($score->score_rationale)->toBe(completedScorePayload()['score_rationale'])
        ->and($score->summary)->toBe(completedScorePayload()['summary'])
        ->and($score->red_flags)->toBe(completedScorePayload()['red_flags'])
        ->and($score->strengths)->toBe(completedScorePayload()['strengths'])
        ->and($score->agent_id)->toBe($agent->id);
});

it('runs one repair retry when the first payload is malformed, then succeeds', function () {
    ScoreAgent::fake([
        ['fit_score' => 150, 'score_rationale' => 'x', 'summary' => 'y', 'red_flags' => [], 'strengths' => []],
        completedScorePayload(),
    ]);

    $application = Application::factory()->create();

    $agent = app(ApplicationScoringService::class)->run($application);

    ScoreAgent::assertPrompted(
        fn ($prompt): bool => str_contains($prompt->prompt, 'YOUR PREVIOUS RESPONSE WAS INVALID')
    );

    expect($agent->status)->toBe(AgentStatus::Completed)
        ->and($application->refresh()->score?->fit_score)->toBe(82);
});

it('fails the agent and writes no Score when the repair retry is still invalid', function () {
    $malformed = ['fit_score' => 150, 'score_rationale' => 'x', 'summary' => 'y', 'red_flags' => [], 'strengths' => []];

    ScoreAgent::fake([$malformed, $malformed]);

    $application = Application::factory()->create();

    $agent = app(ApplicationScoringService::class)->run($application);

    expect($agent->status)->toBe(AgentStatus::Failed)
        ->and($agent->error)->not->toBeEmpty()
        ->and($agent->raw_response)->toBe($malformed)
        ->and(Score::count())->toBe(0);
});

it('mutates the same agent in place on a re-run (1:1)', function () {
    ScoreAgent::fake([completedScorePayload(), completedScorePayload()]);

    $application = Application::factory()->create();
    $service = app(ApplicationScoringService::class);

    $first = $service->run($application);
    $second = $service->run($application);

    expect($second->id)->toBe($first->id)
        ->and($application->refresh()->agents()->count())->toBe(1)
        ->and(Score::count())->toBe(1);
});

it('rejects subjects that are not applications', function () {
    expect(fn () => app(ApplicationScoringService::class)->run(new Score))
        ->toThrow(InvalidArgumentException::class);
});
