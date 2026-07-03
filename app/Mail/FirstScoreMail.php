<?php

namespace App\Mail;

use App\Listeners\SendFirstScoreEmail;
use App\Models\Application;
use App\Onboarding\OnboardingMailer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent to a landlord the moment their very first applicant is scored —
 * real-time, not part of the daily onboarding sweep. Idempotent: dispatched
 * only once per landlord by {@see SendFirstScoreEmail}.
 */
class FirstScoreMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * The canonical subject used as the {@see OnboardingMailer}
     * idempotency key. Must match {@see Envelope()}.
     */
    public const SUBJECT = 'Your first Score is in';

    /**
     * Create a new message instance.
     */
    public function __construct(public Application $application) {}

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
        $unit = $this->application->unit;
        $landlord = $unit->property->landlord;

        return new Content(
            markdown: 'emails.onboarding.first-score',
            with: [
                'firstName' => trim(explode(' ', $landlord->name)[0]) ?: $landlord->name,
                'applicantName' => trim("{$this->application->applicant_first_name} {$this->application->applicant_last_name}"),
                'unitLabel' => $unit->label,
                'unsubscribeUrl' => $landlord->onboardingUnsubscribeUrl(),
            ],
        );
    }
}
