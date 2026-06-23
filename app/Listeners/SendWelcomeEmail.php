<?php

namespace App\Listeners;

use App\Mail\WelcomeMail;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Mail;

class SendWelcomeEmail
{
    /**
     * Send the branded welcome email once a user verifies their email address.
     */
    public function handle(Verified $event): void
    {
        $user = $event->user;

        if (! $user instanceof User) {
            return;
        }

        Mail::to($user)->send(new WelcomeMail($user));
    }
}
