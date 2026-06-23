<?php

namespace App\Models;

use Database\Factories\ApplicationLinkFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $unit_id
 * @property string $token
 * @property string|null $label
 * @property bool $is_accepting
 * @property Carbon|null $expires_at
 * @property Carbon|null $revoked_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'label',
    'is_accepting',
    'expires_at',
    'revoked_at',
])]
class ApplicationLink extends Model
{
    /** @use HasFactory<ApplicationLinkFactory> */
    use HasFactory;

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function (ApplicationLink $link): void {
            if (empty($link->token)) {
                $link->token = Str::random(40);
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_accepting' => 'boolean',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /**
     * Whether the link is currently accepting submissions: accepting, not revoked, not expired.
     */
    public function isOpen(): bool
    {
        if (! $this->is_accepting || $this->revoked_at !== null) {
            return false;
        }

        return $this->expires_at === null || $this->expires_at->isFuture();
    }

    /**
     * Why the link is closed, so the public page can show tailored copy.
     *
     * Returns null when the link is open; otherwise one of `revoked`, `expired`,
     * or `not_accepting` (revocation takes precedence, then expiry).
     */
    public function closedReason(): ?string
    {
        if ($this->isOpen()) {
            return null;
        }

        if ($this->revoked_at !== null) {
            return 'revoked';
        }

        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return 'expired';
        }

        return 'not_accepting';
    }

    /**
     * The unit this application link belongs to.
     *
     * @return BelongsTo<Unit, $this>
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * The applications submitted through this link.
     *
     * @return HasMany<Application, $this>
     */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }
}
