<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Enums\OccupancyStatus;
use App\Enums\RentalType;
use App\Models\Application;
use App\Models\Property;
use App\Models\Unit;
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

            $spaces = $properties->sum(fn (Property $property): int => $property->rental_type === RentalType::MultiUnit ? (int) $property->units_count : 1);
            $occupied = $properties->sum(fn (Property $property): int => $property->rental_type === RentalType::MultiUnit ? (int) $property->occupied_units_count : ($property->status === OccupancyStatus::Occupied ? 1 : 0));
            $available = $properties->sum(fn (Property $property): int => $property->rental_type === RentalType::MultiUnit ? (int) $property->available_units_count : ($property->status === OccupancyStatus::Available ? 1 : 0));

            $newApplications = Application::query()
                ->where('status', ApplicationStatus::New)
                ->whereHas('unit.property', fn ($query) => $query->where('landlord_id', $user->id))
                ->count();

            $totalApplications = Application::query()
                ->whereHas('unit.property', fn ($query) => $query->where('landlord_id', $user->id))
                ->count();

            $busiestUnit = Unit::query()
                ->whereHas('property', fn ($query) => $query->where('landlord_id', $user->id))
                ->withCount('applications')
                ->having('applications_count', '>', 0)
                ->orderByDesc('applications_count')
                ->first();

            $stats = [
                'properties' => $properties->count(),
                'units' => $spaces,
                'occupied' => $occupied,
                'available' => $available,
                'new_applications' => $newApplications,
                'total_applications' => $totalApplications,
                'busiest_unit' => $busiestUnit ? [
                    'id' => $busiestUnit->id,
                    'label' => $busiestUnit->label,
                    'applications_count' => $busiestUnit->applications_count,
                ] : null,
            ];
        }

        return Inertia::render('Dashboard', [
            'stats' => $stats,
        ]);
    }
}
