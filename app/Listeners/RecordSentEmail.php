<?php

namespace App\Listeners;

use App\Models\SentEmail;
use Illuminate\Mail\Events\MessageSent;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class RecordSentEmail
{
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
            'body' => $message->getHtmlBody() ?? $message->getTextBody(),
            'sent_at' => now(),
        ]);
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
