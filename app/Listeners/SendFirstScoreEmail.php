<?php

namespace App\Listeners;

use App\Events\ApplicationScored;
use App\Mail\FirstScoreMail;
use App\Onboarding\OnboardingMailer;
use App\Onboarding\OnboardingState;

/**
 * Sends the real-time "your first Score is in" email the moment a landlord's
 * very first applicant is scored. {@see OnboardingMailer} additionally guards
 * against re-sending if this listener were ever re-run.
 */
class SendFirstScoreEmail
{
    public function __construct(private readonly OnboardingMailer $mailer) {}

    /**
     * Send {@see FirstScoreMail} only when this is the landlord's first-ever
     * scored application.
     */
    public function handle(ApplicationScored $event): void
    {
        $application = $event->application->loadMissing('unit.property.landlord');
        $landlord = $application->unit->property->landlord;

        if ($landlord === null) {
            return;
        }

        $state = new OnboardingState($landlord);

        if ($state->scoredApplicationCount() !== 1) {
            return;
        }

        $this->mailer->send($landlord, new FirstScoreMail($application), FirstScoreMail::SUBJECT);
    }
}
