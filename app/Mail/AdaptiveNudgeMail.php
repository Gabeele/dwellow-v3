<?php

namespace App\Mail;

use App\Enums\OnboardingStep;
use App\Models\User;
use App\Onboarding\OnboardingMailer;
use App\Onboarding\OnboardingState;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use RuntimeException;

/**
 * Day-2 onboarding nudge, sent only while a landlord hasn't yet scored an
 * applicant. State-aware: it computes the landlord's single next unfinished
 * step ({@see OnboardingState::nextStep()}) and renders the matching CTA.
 */
class AdaptiveNudgeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * The canonical subject used as the {@see OnboardingMailer}
     * idempotency key. Must match {@see Envelope()}.
     */
    public const SUBJECT = 'Your next step takes about five minutes';

    /**
     * Create a new message instance.
     */
    public function __construct(public User $landlord) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: self::SUBJECT,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $state = new OnboardingState($this->landlord);
        $step = $state->nextStep();

        return new Content(
            markdown: 'emails.onboarding.adaptive-nudge',
            with: [
                'firstName' => $this->firstName(),
                'step' => $step,
                'ctaLabel' => $this->ctaLabel($step),
                'ctaUrl' => $this->ctaUrl($step, $state),
                'stepDescription' => $this->stepDescription($step),
                'unsubscribeUrl' => $this->landlord->onboardingUnsubscribeUrl(),
            ],
        );
    }

    /**
     * The landlord's first name, falling back to their full name.
     */
    private function firstName(): string
    {
        return trim(explode(' ', $this->landlord->name)[0]) ?: $this->landlord->name;
    }

    /**
     * The CTA button label for the given step.
     */
    private function ctaLabel(OnboardingStep $step): string
    {
        return match ($step) {
            OnboardingStep::AddProperty => 'Add your first property',
            OnboardingStep::BuildApplication => 'Build your application',
            OnboardingStep::ShareLink => 'Copy your link',
            OnboardingStep::Activated => throw new RuntimeException(
                'AdaptiveNudgeMail should never be sent to an activated landlord.'
            ),
        };
    }

    /**
     * The supporting line under the CTA for the given step.
     */
    private function stepDescription(OnboardingStep $step): string
    {
        return match ($step) {
            OnboardingStep::AddProperty => 'Add a property and its units — a couple of minutes.',
            OnboardingStep::BuildApplication => 'Build the application for your unit — you choose the questions and the documents. Once per unit, about five minutes.',
            OnboardingStep::ShareLink => 'Your link is ready — share it in your listing, or reply to the next "is this still available?". Every applicant lands on one dashboard, scored against your criteria.',
            OnboardingStep::Activated => '',
        };
    }

    /**
     * The CTA button URL for the given step.
     */
    private function ctaUrl(OnboardingStep $step, OnboardingState $state): string
    {
        return match ($step) {
            OnboardingStep::AddProperty => route('properties.create'),
            OnboardingStep::BuildApplication => route('units.form.edit', $state->unitNeedingApplication()),
            // The link is copied from its unit's property page (see docs guide
            // "Open screening & share the link"), so that's the closest CTA target.
            OnboardingStep::ShareLink => route('properties.show', $state->openLink()?->unit->property),
            OnboardingStep::Activated => route('dashboard'),
        };
    }
}
