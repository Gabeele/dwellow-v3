<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Models\Agent;
use App\Models\Application;
use App\Models\Property;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
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

        // Both props are lazy closures so the frontend can partial-reload the
        // `agents` table on a poll without re-running the portfolio-stats
        // queries (and vice versa). See the Agents activity table polling.
        return Inertia::render('Dashboard', [
            'stats' => fn (): ?array => $user->isLandlord() ? $this->portfolioStats($user) : null,
            'agents' => fn (): LengthAwarePaginator => $this->recentAgents($user),
        ]);
    }

    /**
     * The landlord's portfolio headline numbers for the dashboard stat cards.
     *
     * @return array{properties: int, units: int, occupied: int, available: int, new_applications: int, total_applications: int, busiest_unit: array{id: int, label: string, applications_count: int}|null}
     */
    private function portfolioStats(User $user): array
    {
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

        return [
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

    /**
     * The landlord's most recent + active agent runs, newest first, shaped for
     * the dashboard "Agents" activity table. Eager-loads the polymorphic
     * subject so the label/url accessors don't N+1 query per row.
     *
     * Paginated at 10 per page (newest first) under the `agentPage` query key, so
     * the dashboard shows the latest runs and the table can page back through the
     * history without flooding the landing render.
     *
     * @return LengthAwarePaginator<int, array{id: int, type: string, type_label: string, subject_type_label: string|null, subject_status: string|null, subject_status_label: string|null, subject_submitted_at: string|null, fit_score: int|null, status: string, status_label: string, subject_label: string|null, url: string|null, created_at: string|null, started_at: string|null, completed_at: string|null}>
     */
    private function recentAgents(User $user): LengthAwarePaginator
    {
        return Agent::query()
            // Eager-load the subject and (for applications) its Score, so the row's
            // application status and fit score don't N+1 query per agent.
            ->with(['analyzable' => fn (MorphTo $morphTo) => $morphTo->morphWith([
                Application::class => ['score'],
            ])])
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
            ->paginate(10, ['*'], 'agentPage')
            ->through(function (Agent $agent): array {
                $application = $agent->analyzable instanceof Application ? $agent->analyzable : null;

                return [
                    'id' => $agent->id,
                    'type' => $agent->type->value,
                    'type_label' => $agent->type->label(),
                    // The kind of subject the agent ran against (e.g. "Application").
                    'subject_type_label' => $application !== null ? class_basename($application) : null,
                    // The subject application's own workflow status, and the fit
                    // score the run produced (null until it completes).
                    'subject_status' => $application?->status->value,
                    'subject_status_label' => $application?->status->label(),
                    'subject_submitted_at' => $application?->submitted_at?->toIso8601String(),
                    'fit_score' => $application?->score?->fit_score,
                    'status' => $agent->status->value,
                    'status_label' => $agent->status->label(),
                    'subject_label' => $agent->subject_label,
                    'url' => $agent->result_url,
                    'created_at' => $agent->created_at?->toIso8601String(),
                    'started_at' => $agent->started_at?->toIso8601String(),
                    'completed_at' => $agent->completed_at?->toIso8601String(),
                ];
            });
    }
}
