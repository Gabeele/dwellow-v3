<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Branded replacement for the default email-verification notification.
 *
 * Inherits the signed verification URL generation from the base class and
 * only customises the presentation with a dwellow-branded Markdown mailable.
 */
class VerifyEmailNotification extends VerifyEmail
{
    /**
     * Build the branded mail message for the given verification URL.
     */
    protected function buildMailMessage($url): MailMessage
    {
        return (new MailMessage)
            ->subject('Verify your dwellow email address')
            ->markdown('emails.verify-email', ['url' => $url]);
    }
}
