<?php

use App\Mail\FirstScoreMail;
use App\Models\Application;
use App\Models\ApplicationLink;
use App\Models\Property;
use App\Models\Unit;
use App\Models\User;
use App\Screening\Agents\ScoreAgent;
use App\Screening\ApplicationScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

function scoreableApplicationFor(User $landlord): Application
{
    $property = Property::factory()->multiUnit()->for($landlord, 'landlord')->create();
    $unit = Unit::factory()->for($property)->create();
    $link = ApplicationLink::factory()->for($unit)->create();

    return Application::factory()->for($link, 'applicationLink')->create(['unit_id' => $unit->id]);
}

/**
 * A valid Score payload matching the locked response contract.
 *
 * @return array{fit_score: int, score_rationale: string, summary: string, rubric: list<array{criterion: string, assessment: string, note: string}>, red_flags: list<string>, strengths: list<string>}
 */
function firstScorePayload(): array
{
    return [
        'fit_score' => 82,
        'score_rationale' => 'Stable income comfortably covers the rent.',
        'summary' => 'The applicant reports steady employment and references.',
        'rubric' => [
            ['criterion' => 'affordability', 'assessment' => 'strong', 'note' => 'under 30% of gross'],
            ['criterion' => 'employment', 'assessment' => 'strong', 'note' => 'stable full-time'],
            ['criterion' => 'credit', 'assessment' => 'adequate', 'note' => 'good'],
            ['criterion' => 'references', 'assessment' => 'strong', 'note' => 'two contactable'],
            ['criterion' => 'rental_history', 'assessment' => 'adequate', 'note' => 'no issues'],
            ['criterion' => 'occupancy', 'assessment' => 'strong', 'note' => 'fits the unit'],
            ['criterion' => 'identity', 'assessment' => 'strong', 'note' => 'ID matches'],
            ['criterion' => 'disclosures', 'assessment' => 'adequate', 'note' => 'no pets'],
        ],
        'red_flags' => [],
        'strengths' => ['Rent-to-income ratio under 30%'],
    ];
}

test('persisting a first Score sends FirstScoreMail exactly once', function () {
    Mail::fake();
    ScoreAgent::fake([firstScorePayload()]);

    $landlord = User::factory()->landlord()->create();
    $application = scoreableApplicationFor($landlord);

    app(ApplicationScoringService::class)->score($application);

    Mail::assertQueued(FirstScoreMail::class, fn (FirstScoreMail $mail) => $mail->hasTo($landlord->email)
        && $mail->application->is($application));
    Mail::assertQueued(FirstScoreMail::class, 1);
});

test('a second scored application does not send FirstScoreMail again', function () {
    Mail::fake();
    ScoreAgent::fake([firstScorePayload(), firstScorePayload()]);

    $landlord = User::factory()->landlord()->create();
    $first = scoreableApplicationFor($landlord);
    $second = scoreableApplicationFor($landlord);

    app(ApplicationScoringService::class)->score($first);
    app(ApplicationScoringService::class)->score($second);

    Mail::assertQueued(FirstScoreMail::class, 1);
});

test('does not send FirstScoreMail to a landlord who has opted out of onboarding emails', function () {
    Mail::fake();
    ScoreAgent::fake([firstScorePayload()]);

    $landlord = User::factory()->landlord()->create(['unsubscribed_from_onboarding_at' => now()]);
    $application = scoreableApplicationFor($landlord);

    app(ApplicationScoringService::class)->score($application);

    Mail::assertNotQueued(FirstScoreMail::class);
});
