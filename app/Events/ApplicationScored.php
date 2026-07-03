<?php

namespace App\Events;

use App\Listeners\SendFirstScoreEmail;
use App\Models\Application;
use App\Models\Score;
use App\Screening\ApplicationScoringService;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired the moment an application's {@see Score} is persisted
 * (see {@see ApplicationScoringService::completeAgent()}).
 * Drives the real-time "first Score" onboarding email — see
 * {@see SendFirstScoreEmail}.
 */
class ApplicationScored
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public Application $application) {}
}
