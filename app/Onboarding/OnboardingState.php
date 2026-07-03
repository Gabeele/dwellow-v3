<?php

namespace App\Onboarding;

use App\Enums\OnboardingStep;
use App\Listeners\SendFirstScoreEmail;
use App\Models\Application;
use App\Models\ApplicationLink;
use App\Models\Unit;
use App\Models\User;

/**
 * Derives a landlord's onboarding progress toward their first scored
 * applicant, used by the adaptive nudge email and the daily onboarding
 * command to decide what (if anything) to send.
 *
 * Activation is intentionally three gates wide: own a property, have an
 * active application link, and have a scored applicant. A fuller "opened
 * dashboard" engagement signal is deferred — see {@see isActivated()}.
 */
class OnboardingState
{
    public function __construct(private readonly User $landlord) {}

    /**
     * Whether the landlord owns at least one property.
     */
    public function hasProperty(): bool
    {
        return $this->landlord->properties()->exists();
    }

    /**
     * Whether the landlord has at least one unit with an active
     * (accepting, unrevoked, unexpired) application link.
     */
    public function hasActiveLink(): bool
    {
        return $this->openLink() !== null;
    }

    /**
     * Whether the landlord has ever had an application scored — the
     * activation milestone this whole sequence is driving toward.
     */
    public function hasScoredApplicant(): bool
    {
        return $this->scoredApplicationCount() > 0;
    }

    /**
     * How many of the landlord's applications have been scored. Used to
     * detect the "first Score" moment ({@see SendFirstScoreEmail}).
     */
    public function scoredApplicationCount(): int
    {
        return Application::query()
            ->whereHas('unit.property', fn ($query) => $query->where('landlord_id', $this->landlord->id))
            ->whereHas('score')
            ->count();
    }

    /**
     * Whether the landlord has cleared every activation gate.
     *
     * NOTE: activation exit today is scoring-only; a fuller "opened the
     * dashboard" signal is intentionally deferred (see class docblock).
     */
    public function isActivated(): bool
    {
        return $this->hasScoredApplicant();
    }

    /**
     * The single next unfinished step, used to pick the adaptive nudge's CTA.
     */
    public function nextStep(): OnboardingStep
    {
        if (! $this->hasProperty()) {
            return OnboardingStep::AddProperty;
        }

        if (! $this->hasActiveLink()) {
            return OnboardingStep::BuildApplication;
        }

        if (! $this->isActivated()) {
            return OnboardingStep::ShareLink;
        }

        return OnboardingStep::Activated;
    }

    /**
     * A unit belonging to the landlord that has no active application link
     * yet, for the "build your application" CTA. Null if every unit already
     * has one (or the landlord has no units).
     */
    public function unitNeedingApplication(): ?Unit
    {
        return Unit::query()
            ->whereHas('property', fn ($query) => $query->where('landlord_id', $this->landlord->id))
            ->whereDoesntHave('applicationLinks', fn ($query) => $query->where('is_accepting', true)
                ->whereNull('revoked_at'))
            ->first();
    }

    /**
     * The landlord's oldest active application link, for the "copy your
     * link" CTA.
     */
    public function openLink(): ?ApplicationLink
    {
        return ApplicationLink::query()
            ->whereHas('unit.property', fn ($query) => $query->where('landlord_id', $this->landlord->id))
            ->where('is_accepting', true)
            ->whereNull('revoked_at')
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->oldest()
            ->first();
    }
}
