<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Models\Agent;
use App\Models\Application;
use App\Models\Property;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
        $agents = [];

        if ($user->isLandlord()) {
            $properties = $user->properties()->withUnitCounts()->get();

            $spaces = $properties->sum(fn (Property $property): int => $property->spaceCount());
            $occupied = $properties->sum(fn (Property $property): int => $property->occupiedSpaceCount());
            $available = $properties->sum(fn (Property $property): int => $property->availableSpaceCount());

            $newApplications = Application::query()
                ->where('status', ApplicationStatus::New)
                ->whereHas('unit.property', fn ($query) => $query->where('landlord_id', $user->id))
                ->count();

            $totalApplications = Application::query()
                ->whereHas('unit.property', fn ($query) => $query->where('landlord_id', $user->id))
                ->count();

            $busiestUnit = Unit::query()
                ->whereHas('property', fn ($query) => $query->where('landlord_id', $user->id))
                ->whereHas('applications')
                ->withCount('applications')
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

            $agents = $this->recentAgents($user);
        }

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'agents' => $agents,
        ]);
    }

    /**
     * The landlord's most recent + active agent runs, newest first, shaped for
     * the dashboard "Agents" activity table. Eager-loads the polymorphic
     * subject so the label/url accessors don't N+1 query per row.
     *
     * @return Collection<int, array{id: int, type: string, type_label: string, status: string, status_label: string, subject_label: string|null, url: string|null, started_at: string|null, completed_at: string|null}>
     */
    private function recentAgents(User $user): Collection
    {
        return Agent::query()
            ->with('analyzable')
            ->whereHasMorph(
                'analyzable',
                [Application::class],
                fn (Builder $query) => $query->whereHas(
                    'unit.property',
                    fn (Builder $property) => $property->where('landlord_id', $user->id),
                ),
            )
            ->latest()
            ->latest('id')
            ->limit(10)
            ->get()
            ->map(fn (Agent $agent): array => [
                'id' => $agent->id,
                'type' => $agent->type->value,
                'type_label' => $agent->type->label(),
                'status' => $agent->status->value,
                'status_label' => $agent->status->label(),
                'subject_label' => $agent->subject_label,
                'url' => $agent->result_url,
                'started_at' => $agent->started_at?->toIso8601String(),
                'completed_at' => $agent->completed_at?->toIso8601String(),
            ]);
    }
}
