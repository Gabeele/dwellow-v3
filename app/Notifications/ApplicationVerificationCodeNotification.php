<?php

namespace App\Notifications;

use App\Screening\EmailVerification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Mails a prospective tenant their one-time application verification code.
 *
 * Sent on-demand (the applicant has no account) before an application can be
 * submitted, so submissions are tied to a confirmed email address.
 */
class ApplicationVerificationCodeNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public string $code) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Dwellow application code')
            ->markdown('emails.application-code', [
                'code' => $this->code,
                'minutes' => EmailVerification::TTL_MINUTES,
            ]);
    }
}
