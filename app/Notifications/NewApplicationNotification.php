<?php

namespace App\Notifications;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifies the owning landlord that a new application has arrived for one of their units.
 */
class NewApplicationNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Application $application) {}

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
        $unit = $this->application->unit;
        $property = $unit->property;
        $applicantName = trim("{$this->application->applicant_first_name} {$this->application->applicant_last_name}");

        return (new MailMessage)
            ->subject('New application received')
            ->greeting('You have a new application')
            ->line("{$applicantName} applied for {$property->address_line1} ({$unit->label}).")
            ->action('Review application', route('applicants.show', $this->application))
            ->line('Sign in to Dwellow to review their details and documents.');
    }
}
