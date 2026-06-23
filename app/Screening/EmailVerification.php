<?php

namespace App\Screening;

use App\Models\ApplicationLink;
use App\Notifications\ApplicationVerificationCodeNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

/**
 * Lightweight, account-free email verification for public applicants.
 *
 * A one-time numeric code is mailed to the applicant's address and cached against
 * the link + email for a short window. The submission is gated on a matching code
 * so applications are attributable and spammy links are deterred — there is no
 * applicant account, so the code is the only proof of email ownership.
 */
class EmailVerification
{
    /**
     * How long a freshly issued code remains valid.
     */
    public const TTL_MINUTES = 15;

    /**
     * Issue a new code for the email, cache it, and mail it to the applicant.
     */
    public function send(ApplicationLink $link, string $email): void
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        Cache::put($this->cacheKey($link, $email), $code, now()->addMinutes(self::TTL_MINUTES));

        Notification::route('mail', $email)
            ->notify(new ApplicationVerificationCodeNotification($code));
    }

    /**
     * Determine whether the supplied code matches the one issued for the email.
     *
     * A correct code is consumed (forgotten) so it cannot be replayed.
     */
    public function verify(ApplicationLink $link, string $email, string $code): bool
    {
        $expected = Cache::get($this->cacheKey($link, $email));

        if ($expected === null || ! hash_equals($expected, $code)) {
            return false;
        }

        Cache::forget($this->cacheKey($link, $email));

        return true;
    }

    /**
     * The cache key scoping a code to a single link + email address.
     */
    private function cacheKey(ApplicationLink $link, string $email): string
    {
        return 'screening-code:'.$link->id.':'.sha1(mb_strtolower(trim($email)));
    }
}
