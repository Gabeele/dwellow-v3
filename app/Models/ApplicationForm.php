<?php

namespace App\Models;

use Database\Factories\ApplicationFormFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $unit_id
 * @property array<int, array<string, mixed>> $sections
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'sections',
])]
class ApplicationForm extends Model
{
    /** @use HasFactory<ApplicationFormFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sections' => 'array',
        ];
    }

    /**
     * The unit this application form belongs to.
     *
     * @return BelongsTo<Unit, $this>
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * The sections an applicant actually sees: every locked or enabled section.
     *
     * @return list<array<string, mixed>>
     */
    public function enabledSections(): array
    {
        return array_values(array_filter(
            $this->sections ?? [],
            fn (array $section): bool => ($section['locked'] ?? false) === true
                || ($section['enabled'] ?? true) !== false,
        ));
    }

    /**
     * The flat, ordered list of fields from every enabled section — the single
     * source of truth for rendering, validating, and snapshotting a submission.
     *
     * @return list<array<string, mixed>>
     */
    public function enabledFields(): array
    {
        $fields = [];

        foreach ($this->enabledSections() as $section) {
            foreach ($section['fields'] ?? [] as $field) {
                $fields[] = $field;
            }
        }

        return $fields;
    }
}
