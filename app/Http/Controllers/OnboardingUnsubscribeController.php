<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * Handles the CASL unsubscribe link carried in every promotional onboarding
 * email. Stamping `unsubscribed_from_onboarding_at` skips all future
 * onboarding sends (the daily command and the real-time first-score email)
 * for this landlord; transactional email is unaffected.
 */
class OnboardingUnsubscribeController extends Controller
{
    /**
     * Opt the given user out of the onboarding email sequence. The route is
     * signed so the link works without authentication, and idempotent so a
     * repeat click (or link scanner) never errors.
     */
    public function __invoke(User $user): RedirectResponse
    {
        if (! $user->hasUnsubscribedFromOnboarding()) {
            $user->forceFill(['unsubscribed_from_onboarding_at' => now()])->save();
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "You won't receive any more onboarding emails from Dwellow.",
        ]);

        return redirect()->route('home');
    }
}
