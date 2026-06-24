<?php

namespace App\Mail;

use App\Models\Application;
use App\Models\Property;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent to an applicant when the landlord declines their application — either
 * directly, or because another applicant was approved for the same unit.
 */
class ApplicationRejectedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

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
            subject: 'An update on your application',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $unit = $this->application->unit;
        $property = $unit->property;

        return new Content(
            markdown: 'emails.application-rejected',
            with: [
                'firstName' => $this->application->applicant_first_name,
                'unitLabel' => $unit->label,
                'address' => $this->formatAddress($property),
            ],
        );
    }

    /**
     * Join a property's address parts into a single human-readable line.
     */
    private function formatAddress(Property $property): string
    {
        return collect([
            $property->address_line1,
            $property->address_line2,
            $property->city,
            $property->region,
            $property->postal_code,
        ])->filter()->implode(', ');
    }
}
