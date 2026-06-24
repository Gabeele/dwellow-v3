<?php

namespace App\Models;

use App\Enums\OccupancyStatus;
use App\Enums\PropertyType;
use App\Enums\RentalType;
use App\Observers\PropertyObserver;
use Database\Factories\PropertyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
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
}
