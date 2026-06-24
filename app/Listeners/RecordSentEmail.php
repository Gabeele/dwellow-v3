<?php

namespace App\Listeners;

use App\Models\SentEmail;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Mail\Events\MessageSent;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class RecordSentEmail
{
    /**
     * Placeholder stored in place of a sensitive email body so the audit row
     * still records that the email was sent, without persisting the secret.
     */
    public const REDACTED_BODY = '[Redacted: this email contains a single-use security link and its body is not stored.]';

    /**
     * Notification classes whose emails carry single-use account-takeover links
     * (password reset, email verification). Their bodies are never persisted.
     * Subclasses are matched too, so branded overrides are covered.
     *
     * @var array<int, class-string>
     */
    protected const SENSITIVE_NOTIFICATIONS = [
        ResetPassword::class,
        VerifyEmail::class,
    ];

    /**
     * Persist a record of every outgoing email so admins can review it.
     */
    public function handle(MessageSent $event): void
    {
        $message = $event->message;

        SentEmail::create([
            'mailer' => $event->data['mailer'] ?? null,
            'subject' => $message->getSubject(),
            'from' => $this->addresses($message->getFrom())[0] ?? null,
            'to' => $this->addresses($message->getTo()),
            'cc' => $this->addresses($message->getCc()),
            'bcc' => $this->addresses($message->getBcc()),
            'body' => $this->bodyFor($event, $message),
            'sent_at' => now(),
        ]);
    }

    /**
     * Resolve the body to store, redacting emails that carry sensitive links.
     */
    protected function bodyFor(MessageSent $event, Email $message): ?string
    {
        if ($this->containsSensitiveLink($event)) {
            return self::REDACTED_BODY;
        }

        return $message->getHtmlBody() ?? $message->getTextBody();
    }

    /**
     * Determine whether the email originated from a security notification whose
     * body contains a single-use link that must not be stored.
     */
    protected function containsSensitiveLink(MessageSent $event): bool
    {
        $notification = $event->data['__laravel_notification'] ?? null;

        if (! is_string($notification)) {
            return false;
        }

        foreach (self::SENSITIVE_NOTIFICATIONS as $sensitive) {
            if (is_a($notification, $sensitive, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Flatten Symfony mail addresses into plain email strings.
     *
     * @param  array<int, Address>  $addresses
     * @return array<int, string>
     */
    protected function addresses(array $addresses): array
    {
        return array_map(fn (Address $address): string => $address->getAddress(), $addresses);
    }
}
