<?php

namespace App\Models;

use App\Concerns\HasRoles;
use App\Enums\Role;
use App\Notifications\VerifyEmailNotification;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property Carbon|null $unsubscribed_from_onboarding_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, TwoFactorAuthenticatable;

    /**
     * Determine whether the user may access the Filament admin panel.
     *
     * Access is granted to users holding the Admin role, with the email
     * allowlist in config/admin.php (set via ADMIN_EMAILS) as a fallback.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isAdmin() || in_array($this->email, config('admin.emails'), true);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'unsubscribed_from_onboarding_at' => 'datetime',
        ];
    }

    /**
     * Send the branded Dwellow email verification notification.
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification);
    }

    /**
     * The properties this user owns as a landlord.
     *
     * @return HasMany<Property, $this>
     */
    public function properties(): HasMany
    {
        return $this->hasMany(Property::class, 'landlord_id');
    }

    /**
     * Whether this user has opted out of promotional onboarding emails
     * (CASL unsubscribe). Transactional email is unaffected.
     */
    public function hasUnsubscribedFromOnboarding(): bool
    {
        return $this->unsubscribed_from_onboarding_at !== null;
    }

    /**
     * Scope a query to users who have not opted out of onboarding emails.
     *
     * @param  Builder<User>  $query
     */
    public function scopeSubscribedToOnboarding(Builder $query): void
    {
        $query->whereNull('unsubscribed_from_onboarding_at');
    }

    /**
     * Scope a query to users holding the landlord role.
     *
     * @param  Builder<User>  $query
     */
    public function scopeLandlords(Builder $query): void
    {
        $query->whereHas('roles', fn (Builder $roles) => $roles->where('role', Role::Landlord));
    }

    /**
     * The signed CASL unsubscribe link for the promotional onboarding
     * sequence, carried in the footer of every onboarding email.
     */
    public function onboardingUnsubscribeUrl(): string
    {
        return URL::signedRoute('onboarding.unsubscribe', ['user' => $this]);
    }
}
