<?php

use App\Enums\ActivityType;
use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\ApplicationLink;
use App\Models\Property;
use App\Models\Unit;
use App\Models\User;
use App\Screening\Agents\ScoreAgent;
use App\Screening\ApplicationScoringService;
use App\Screening\ScoringFramework;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

/**
 * A New application on one of the landlord's units.
 */
function activityApplicationFor(User $landlord, ApplicationStatus $status = ApplicationStatus::New): Application
{
    $unit = Unit::factory()
        ->for(Property::factory()->for($landlord, 'landlord')->multiUnit()->create())
        ->create();

    return Application::factory()
        ->for(ApplicationLink::factory()->for($unit)->create(), 'applicationLink')
        ->create(['status' => $status]);
}

/**
 * A complete, valid Score payload (the eight-criterion rubric graded).
 *
 * @return array<string, mixed>
 */
function activityScorePayload(): array
{
    return [
        'fit_score' => 82,
        'score_rationale' => 'Solid application.',
        'summary' => 'The applicant looks solid across the board.',
        'rubric' => array_map(fn (string $criterion): array => [
            'criterion' => $criterion,
            'assessment' => 'adequate',
            'note' => 'fine',
        ], ScoringFramework::keys()),
        'red_flags' => [],
        'strengths' => [],
    ];
}

it('records analysis started and completed activities when scoring runs', function () {
    ScoreAgent::fake([activityScorePayload()]);
    $application = Application::factory()->create();

    app(ApplicationScoringService::class)->run($application);

    // Builder::pluck applies the enum cast, so the column comes back as enums.
    $types = $application->activities()->pluck('type');

    expect($types)
        ->toContain(ActivityType::AnalysisStarted)
        ->toContain(ActivityType::AnalysisCompleted);

    $completed = $application->activities()
        ->where('type', ActivityType::AnalysisCompleted->value)
        ->first();

    expect($completed->causer_id)->toBeNull()
        ->and($completed->meta['fit_score'])->toBe(82);
});

it('records an analysis failed activity when scoring cannot complete', function () {
    $malformed = ['fit_score' => 150, 'score_rationale' => 'x', 'summary' => 'y', 'rubric' => [], 'red_flags' => [], 'strengths' => []];
    ScoreAgent::fake([$malformed, $malformed]);
    $application = Application::factory()->create();

    app(ApplicationScoringService::class)->run($application);

    expect($application->activities()->pluck('type'))
        ->toContain(ActivityType::AnalysisFailed);
});

it('records a reviewing activity, attributed to the landlord, when a New application is marked read', function () {
    $landlord = User::factory()->landlord()->create();
    $application = activityApplicationFor($landlord);

    $this->actingAs($landlord)->post(route('applicants.read', $application));

    $activity = $application->activities()
        ->where('type', ActivityType::MarkedReviewing->value)
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->causer_id)->toBe($landlord->id);
});

it('exposes the activity timeline on the detail page', function () {
    $landlord = User::factory()->landlord()->create();
    $application = activityApplicationFor($landlord);
    $application->recordActivity(ActivityType::Submitted, 'Application submitted');

    $this->withoutVite();

    $this->actingAs($landlord)
        ->get(route('applicants.show', $application))
        ->assertInertia(fn (Assert $page) => $page
            ->has('activities', 1)
            ->where('activities.0.type', 'submitted')
            ->where('activities.0.description', 'Application submitted')
            ->where('activities.0.is_system', false),
        );
});
