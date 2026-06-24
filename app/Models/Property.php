<?php

namespace App\Models;

use App\Enums\OccupancyStatus;
use App\Enums\PropertyType;
use App\Enums\RentalType;
use App\Observers\PropertyObserver;
use Database\Factories\PropertyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $landlord_id
 * @property string|null $name
 * @property string $address_line1
 * @property string|null $address_line2
 * @property string $city
 * @property string $region
 * @property string $postal_code
 * @property string $country
 * @property PropertyType $type
 * @property RentalType $rental_type
 * @property int|null $bedrooms
 * @property string|null $bathrooms
 * @property string|null $rent_amount
 * @property OccupancyStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read int|null $units_count
 * @property-read int|null $occupied_units_count
 * @property-read int|null $available_units_count
 */
#[Fillable([
    'name',
    'address_line1',
    'address_line2',
    'city',
    'region',
    'postal_code',
    'country',
    'type',
    'rental_type',
    'bedrooms',
    'bathrooms',
    'rent_amount',
    'status',
])]
#[ObservedBy(PropertyObserver::class)]
class Property extends Model
{
    /** @use HasFactory<PropertyFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => PropertyType::class,
            'rental_type' => RentalType::class,
            'status' => OccupancyStatus::class,
            'rent_amount' => 'decimal:2',
            'bathrooms' => 'decimal:1',
        ];
    }

    /**
     * The landlord who owns this property.
     *
     * @return BelongsTo<User, $this>
     */
    public function landlord(): BelongsTo
    {
        return $this->belongsTo(User::class, 'landlord_id');
    }

    /**
     * The units belonging to this property (multi-unit rentals only).
     *
     * @return HasMany<Unit, $this>
     */
    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    /**
     * Eager-load the total, occupied, and available unit counts as
     * `units_count`, `occupied_units_count`, and `available_units_count`.
     *
     * @param  Builder<Property>  $query
     */
    public function scopeWithUnitCounts(Builder $query): void
    {
        $query->withCount([
            'units',
            'units as occupied_units_count' => fn (Builder $units) => $units->where('status', OccupancyStatus::Occupied),
            'units as available_units_count' => fn (Builder $units) => $units->where('status', OccupancyStatus::Available),
        ]);
    }

    /**
     * The number of rentable spaces: each unit for a multi-unit property, or the
     * property itself (one space) for a whole rental. Requires {@see scopeWithUnitCounts}.
     */
    public function spaceCount(): int
    {
        return $this->rental_type === RentalType::MultiUnit ? (int) $this->units_count : 1;
    }

    /**
     * The number of occupied spaces. Requires {@see scopeWithUnitCounts}.
     */
    public function occupiedSpaceCount(): int
    {
        return $this->rental_type === RentalType::MultiUnit
            ? (int) $this->occupied_units_count
            : ($this->status === OccupancyStatus::Occupied ? 1 : 0);
    }

    /**
     * The number of available spaces. Requires {@see scopeWithUnitCounts}.
     */
    public function availableSpaceCount(): int
    {
        return $this->rental_type === RentalType::MultiUnit
            ? (int) $this->available_units_count
            : ($this->status === OccupancyStatus::Available ? 1 : 0);
    }
}
