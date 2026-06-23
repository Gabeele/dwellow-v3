<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use Database\Factories\ApplicationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $public_id
 * @property int $application_link_id
 * @property int $unit_id
 * @property string $applicant_first_name
 * @property string $applicant_last_name
 * @property string $applicant_email
 * @property string $applicant_phone
 * @property array<string, mixed> $answers
 * @property array<int, array<string, mixed>> $form_snapshot
 * @property ApplicationStatus $status
 * @property string|null $landlord_notes
 * @property Carbon|null $submitted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'applicant_first_name',
    'applicant_last_name',
    'applicant_email',
    'applicant_phone',
    'answers',
    'form_snapshot',
    'status',
    'landlord_notes',
    'submitted_at',
])]
class Application extends Model
{
    /** @use HasFactory<ApplicationFactory> */
    use HasFactory;

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function (Application $application): void {
            if (empty($application->public_id)) {
                $application->public_id = (string) Str::ulid();
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
            'answers' => 'array',
            'form_snapshot' => 'array',
            'status' => ApplicationStatus::class,
            'submitted_at' => 'datetime',
        ];
    }

    /**
     * The link this application was submitted through.
     *
     * @return BelongsTo<ApplicationLink, $this>
     */
    public function applicationLink(): BelongsTo
    {
        return $this->belongsTo(ApplicationLink::class);
    }

    /**
     * The unit this application is for.
     *
     * @return BelongsTo<Unit, $this>
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * The documents uploaded with this application.
     *
     * @return HasMany<Document, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }
}
