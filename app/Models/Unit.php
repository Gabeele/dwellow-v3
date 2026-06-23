<?php

namespace App\Models;

use App\Enums\OccupancyStatus;
use App\Observers\UnitObserver;
use Database\Factories\UnitFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
}
