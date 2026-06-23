<?php

namespace App\Http\Controllers;

use App\Enums\OccupancyStatus;
use App\Enums\RentalType;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Render the dashboard landing with real portfolio stats for landlords.
     *
     * Non-landlords receive a null `stats` prop so the page can show an
     * honest welcome panel without fabricated numbers.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $stats = null;

        if ($user->isLandlord()) {
            $properties = $user->properties()->withCount([
                'units',
                'units as occupied_units_count' => fn ($query) => $query->where('status', OccupancyStatus::Occupied),
                'units as available_units_count' => fn ($query) => $query->where('status', OccupancyStatus::Available),
            ])->get();

            $spaces = $properties->sum(fn ($property) => $property->rental_type === RentalType::MultiUnit ? $property->units_count : 1);
            $occupied = $properties->sum(fn ($property) => $property->rental_type === RentalType::MultiUnit ? $property->occupied_units_count : ($property->status === OccupancyStatus::Occupied ? 1 : 0));
            $available = $properties->sum(fn ($property) => $property->rental_type === RentalType::MultiUnit ? $property->available_units_count : ($property->status === OccupancyStatus::Available ? 1 : 0));

            $stats = [
                'properties' => $properties->count(),
                'units' => $spaces,
                'occupied' => $occupied,
                'available' => $available,
            ];
        }

        return Inertia::render('Dashboard', [
            'stats' => $stats,
        ]);
    }
}
