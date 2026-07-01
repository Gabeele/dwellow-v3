<?php

namespace App\Models;

use App\Enums\ActivityType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * A single entry on a subject's activity timeline — what happened, who caused it
 * (null for system/AI events), and a human description. Immutable once written.
 *
 * @property int $id
 * @property string $subject_type
 * @property int $subject_id
 * @property int|null $causer_id
 * @property ActivityType $type
 * @property string $description
 * @property array<string, mixed>|null $meta
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'type',
    'description',
    'meta',
    'causer_id',
])]
class Activity extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ActivityType::class,
            'meta' => 'array',
        ];
    }

    /**
     * The subject the activity happened to (an Application today).
     *
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * The user who caused the activity, or null for system / AI events.
     *
     * @return BelongsTo<User, $this>
     */
    public function causer(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
