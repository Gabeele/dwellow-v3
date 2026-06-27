<?php

namespace App\Models;

use Database\Factories\ApplicationDraftDocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A file uploaded against an in-progress draft, mirroring {@link Document}.
 * On submit these are migrated into the application's documents.
 *
 * @property int $id
 * @property int $application_draft_id
 * @property string $field_key
 * @property string $disk
 * @property string $path
 * @property string $original_name
 * @property string|null $mime_type
 * @property int|null $size
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'field_key',
    'disk',
    'path',
    'original_name',
    'mime_type',
    'size',
])]
class ApplicationDraftDocument extends Model
{
    /** @use HasFactory<ApplicationDraftDocumentFactory> */
    use HasFactory;

    /**
     * The draft this file was uploaded for.
     *
     * @return BelongsTo<ApplicationDraft, $this>
     */
    public function draft(): BelongsTo
    {
        return $this->belongsTo(ApplicationDraft::class, 'application_draft_id');
    }
}
