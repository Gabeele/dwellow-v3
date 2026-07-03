<?php

namespace App\Mail;

use App\Models\User;
use App\Onboarding\OnboardingMailer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Day-10 founder check-in, sent only while a landlord still hasn't scored an
 * applicant. First-person voice, no CTA button — the ask is a reply.
 */
class CheckInMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * The canonical subject used as the {@see OnboardingMailer}
     * idempotency key. Must match {@see Envelope()}.
     */
    public const SUBJECT = "How's screening going?";

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
        return new Content(
            markdown: 'emails.onboarding.check-in',
            with: [
                'firstName' => trim(explode(' ', $this->landlord->name)[0]) ?: $this->landlord->name,
                'unsubscribeUrl' => $this->landlord->onboardingUnsubscribeUrl(),
            ],
        );
    }
}
