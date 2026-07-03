<?php

namespace App\Console\Commands;

use App\Mail\AdaptiveNudgeMail;
use App\Mail\CheckInMail;
use App\Models\User;
use App\Onboarding\OnboardingMailer;
use App\Onboarding\OnboardingState;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('dwellow:send-onboarding-emails')]
#[Description('Send the lean landlord onboarding sequence: a Day-2 adaptive nudge and a Day-10 check-in, to landlords who have not yet activated.')]
class SendOnboardingEmails extends Command
{
    /**
     * Send the Day-2 adaptive nudge and Day-10 check-in to every eligible,
     * not-yet-activated landlord. Idempotent via {@see OnboardingMailer}, and
     * skips landlords who have opted out (CASL unsubscribe).
     *
     * Activation exit today is scoring-only (see {@see OnboardingState::isActivated()}).
     * NOTE: a fuller "opened the dashboard" exit signal is intentionally deferred.
     */
    public function handle(OnboardingMailer $mailer): int
    {
        $landlords = User::query()
            ->landlords()
            ->subscribedToOnboarding()
            ->get();

        $nudged = 0;
        $checkedIn = 0;

        foreach ($landlords as $landlord) {
            $state = new OnboardingState($landlord);

            if ($state->isActivated()) {
                continue;
            }

            $signupAge = $landlord->created_at->diffInDays(now());

            if ($signupAge >= 2 && $mailer->send($landlord, new AdaptiveNudgeMail($landlord), AdaptiveNudgeMail::SUBJECT)) {
                $nudged++;
            }

            if ($signupAge >= 10 && $mailer->send($landlord, new CheckInMail($landlord), CheckInMail::SUBJECT)) {
                $checkedIn++;
            }
        }

        $this->info("Sent {$nudged} adaptive nudge(s) and {$checkedIn} check-in(s).");

        return self::SUCCESS;
    }
}
