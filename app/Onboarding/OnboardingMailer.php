<?php

namespace App\Onboarding;

use App\Listeners\RecordSentEmail;
use App\Models\SentEmail;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

/**
 * Sends promotional onboarding emails with {@see SentEmail} as the
 * idempotency key, so a landlord is never emailed the same onboarding step
 * twice (e.g. a re-run of the daily command) and never emailed once they've
 * opted out via CASL unsubscribe.
 *
 * {@see RecordSentEmail} already audits every real send, but
 * only fires once mail actually goes out — it never runs under `Mail::fake()`.
 * This mailer writes its own {@see SentEmail} row up front so the idempotency
 * check is reliable under both real sending and `Mail::fake()` in tests.
 */
class OnboardingMailer
{
    /**
     * Send the given onboarding mailable to the landlord, unless they've
     * unsubscribed or a {@see SentEmail} row already proves this canonical
     * subject was sent to their address.
     *
     * Returns whether the email was sent.
     */
    public function send(User $landlord, Mailable $mailable, string $canonicalSubject): bool
    {
        if ($landlord->hasUnsubscribedFromOnboarding()) {
            return false;
        }

        if ($this->alreadySent($landlord, $canonicalSubject)) {
            return false;
        }

        Mail::to($landlord)->send($mailable);

        SentEmail::create([
            'mailer' => config('mail.default'),
            'subject' => $canonicalSubject,
            'from' => config('mail.from.address'),
            'to' => [$landlord->email],
            'sent_at' => now(),
        ]);

        return true;
    }

    /**
     * Whether a {@see SentEmail} already records this canonical subject
     * having gone out to the landlord's address.
     */
    public function alreadySent(User $landlord, string $canonicalSubject): bool
    {
        return SentEmail::query()
            ->where('subject', $canonicalSubject)
            ->whereJsonContains('to', $landlord->email)
            ->exists();
    }
}
