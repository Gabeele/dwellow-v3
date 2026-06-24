<?php

namespace App\Models;

use App\Enums\OccupancyStatus;
use App\Observers\UnitObserver;
use App\Screening\DefaultApplicationForm;
use Database\Factories\UnitFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $property_id
 * @property string $label
 * @property int|null $bedrooms
 * @property string|null $bathrooms
 * @property string|null $rent_amount
 * @property OccupancyStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[ObservedBy(UnitObserver::class)]
#[Fillable([
    'label',
    'bedrooms',
    'bathrooms',
    'rent_amount',
    'status',
])]
class Unit extends Model
{
    /** @use HasFactory<UnitFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OccupancyStatus::class,
            'rent_amount' => 'decimal:2',
            'bathrooms' => 'decimal:1',
        ];
    }

    /**
     * The property this unit belongs to.
     *
     * @return BelongsTo<Property, $this>
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * The application form configured for this unit.
     *
     * @return HasOne<ApplicationForm, $this>
     */
    public function applicationForm(): HasOne
    {
        return $this->hasOne(ApplicationForm::class);
    }

    /**
     * Get this unit's application form, seeding it from the dwellow default
     * catalog if it does not exist yet. The seed lives here so every call site
     * (the unit observer and the form builder) shares one source of truth.
     */
    public function applicationFormOrDefault(): ApplicationForm
    {
        return $this->applicationForm()->firstOrCreate([], [
            'sections' => DefaultApplicationForm::sections(),
        ]);
    }

    /**
     * The application links generated for this unit.
     *
     * @return HasMany<ApplicationLink, $this>
     */
    public function applicationLinks(): HasMany
    {
        return $this->hasMany(ApplicationLink::class);
    }

    /**
     * The single shareable application link for this unit.
     *
     * A unit has exactly one link, toggled on or off via its `is_accepting`
     * flag. The newest row wins so units carried over from the earlier
     * multi-link era resolve to a single canonical link.
     *
     * @return HasOne<ApplicationLink, $this>
     */
    public function applicationLink(): HasOne
    {
        return $this->hasOne(ApplicationLink::class)->latestOfMany();
    }

    /**
     * Get this unit's application link, creating it if it does not exist yet.
     *
     * Mirrors applicationFormOrDefault(): the unit observer seeds the link on
     * creation, and this heals any unit provisioned before that existed so the
     * screening surface always has a stable URL to share.
     */
    public function applicationLinkOrDefault(): ApplicationLink
    {
        return $this->applicationLink()->getResults()
            ?? $this->applicationLinks()->create([]);
    }

    /**
     * The applications submitted for this unit.
     *
     * @return HasMany<Application, $this>
     */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }
}
