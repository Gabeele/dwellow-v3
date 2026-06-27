<?php

namespace App\Models;

use Database\Factories\ApplicationDraftFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * An in-progress, account-free application kept so a prospective tenant can
 * resume the screening wizard after closing their browser. Identified by an
 * unguessable token held in a per-link cookie; never surfaced to landlords.
 *
 * @property int $id
 * @property int $application_link_id
 * @property string $token
 * @property array<string, mixed>|null $answers
 * @property int $current_step
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'answers',
    'current_step',
])]
class ApplicationDraft extends Model
{
    /** @use HasFactory<ApplicationDraftFactory> */
    use HasFactory;

    use Prunable;

    /**
     * How long an untouched draft is kept before it is pruned along with its files.
     */
    private const int STALE_AFTER_DAYS = 30;

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function (ApplicationDraft $draft): void {
            if (empty($draft->token)) {
                $draft->token = Str::random(40);
            }
        });

        // Delete the draft's stored files whenever the draft goes away — on
        // submit (explicit delete) or via pruning. The DB cascade only removes
        // the document rows, never the files on disk.
        static::deleting(function (ApplicationDraft $draft): void {
            $draft->deleteStoredFiles();
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
            'current_step' => 'integer',
        ];
    }

    /**
     * Abandoned drafts: untouched for longer than the retention window.
     *
     * @return Builder<ApplicationDraft>
     */
    public function prunable(): Builder
    {
        return static::where('updated_at', '<', Carbon::now()->subDays(self::STALE_AFTER_DAYS));
    }

    /**
     * The cookie name that carries this applicant's draft token for a link.
     *
     * Scoped per link so one browser can have independent drafts for different
     * units without the cookies colliding.
     */
    public static function cookieName(ApplicationLink $link): string
    {
        return "draft_link_{$link->getKey()}";
    }

    /**
     * Resolve the draft a token refers to for a given link, if any.
     *
     * The token comes from an unguessable, per-link cookie; pairing it with the
     * link id means a leaked token can only ever address its own link's draft.
     *
     * @param  array<array-key, mixed>|string|null  $token  Raw cookie value (an array is never valid)
     */
    public static function forToken(ApplicationLink $link, array|string|null $token): ?self
    {
        if (! is_string($token) || $token === '') {
            return null;
        }

        return static::query()
            ->where('application_link_id', $link->getKey())
            ->where('token', $token)
            ->first();
    }

    /**
     * Remove this draft's uploaded files from disk before the row is deleted.
     */
    public function deleteStoredFiles(): void
    {
        foreach ($this->documents()->get() as $document) {
            Storage::disk($document->disk)->delete($document->path);
        }
    }

    /**
     * The shareable link this draft is being filled out against.
     *
     * @return BelongsTo<ApplicationLink, $this>
     */
    public function applicationLink(): BelongsTo
    {
        return $this->belongsTo(ApplicationLink::class);
    }

    /**
     * The files already uploaded against this draft, keyed by form field.
     *
     * @return HasMany<ApplicationDraftDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(ApplicationDraftDocument::class);
    }
}
