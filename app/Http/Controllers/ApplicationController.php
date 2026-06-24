<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Http\Requests\UpdateApplicationRequest;
use App\Http\Resources\ApplicationRowResource;
use App\Models\Application;
use App\Models\Property;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApplicationController extends Controller
{
    /**
     * List every application across all of the authenticated landlord's units,
     * newest first — the portfolio-wide running list of who has applied.
     *
     * Filterable by status and property, and searchable over the applicant's
     * name/email. Filters live in the query string so a view is shareable.
     */
    public function indexAll(Request $request): Response
    {
        $this->authorize('viewAny', Application::class);

        $user = $request->user();
        $filters = $this->filters($request);

        $applications = $this->landlordApplicationsQuery($request)
            ->with('unit.property')
            ->withCount('documents')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Application $application): array => ApplicationRowResource::make($application)->resolve($request));

        $properties = Property::query()
            ->where('landlord_id', $user->id)
            ->orderBy('name')
            ->orderBy('address_line1')
            ->get()
            ->map(fn (Property $property): array => [
                'id' => $property->id,
                'name' => $property->name ?? $property->address_line1,
            ]);

        return Inertia::render('screening/applicants/All', [
            'applications' => $applications,
            'properties' => $properties,
            'statuses' => array_map(
                fn (ApplicationStatus $case): array => ['value' => $case->value, 'label' => $case->label()],
                ApplicationStatus::cases(),
            ),
            'filters' => [
                'search' => $filters['search'],
                'status' => $filters['status'] instanceof ApplicationStatus ? $filters['status']->value : '',
                'property' => $filters['property'],
            ],
        ]);
    }

    /**
     * Stream the authenticated landlord's applications as a CSV, respecting the
     * same status/property/search filters as the index. Contact details, unit /
     * property, status, and submitted date only — documents stay private and are
     * never included.
     */
    public function exportAll(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Application::class);

        $applications = $this->landlordApplicationsQuery($request)->with('unit.property');

        $headers = ['Applicant name', 'Email', 'Property', 'Unit', 'Status', 'Submitted at'];

        return response()->streamDownload(function () use ($applications, $headers): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, $headers);

            $applications->chunk(200, function ($chunk) use ($handle): void {
                foreach ($chunk as $application) {
                    fputcsv($handle, [
                        trim("{$application->applicant_first_name} {$application->applicant_last_name}"),
                        $application->applicant_email,
                        $application->unit->property->name ?? $application->unit->property->address_line1,
                        $application->unit->label,
                        $application->status->label(),
                        $application->submitted_at?->toDateTimeString() ?? '',
                    ]);
                }
            });

            fclose($handle);
        }, 'applications.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Build the base query for the authenticated landlord's applications, scoped
     * to their units and narrowed by the request's status/property/search filters.
     * Shared by the index page and the CSV export so both honour the same filters.
     *
     * @return Builder<Application>
     */
    private function landlordApplicationsQuery(Request $request): Builder
    {
        $user = $request->user();

        ['search' => $search, 'status' => $status, 'property' => $propertyId] = $this->filters($request);

        return Application::query()
            ->whereHas('unit.property', fn ($query) => $query->where('landlord_id', $user->id))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($propertyId, fn ($query, $propertyId) => $query->whereHas(
                'unit',
                fn ($unitQuery) => $unitQuery->where('property_id', $propertyId),
            ))
            ->when($search !== '', fn ($query) => $query->where(fn ($matches) => $matches
                ->where('applicant_first_name', 'like', "%{$search}%")
                ->orWhere('applicant_last_name', 'like', "%{$search}%")
                ->orWhere('applicant_email', 'like', "%{$search}%")))
            ->latest('submitted_at');
    }

    /**
     * Parse the shared status/property/search filters from the request once, so
     * the index page and its query read them the same way.
     *
     * @return array{search: string, status: ApplicationStatus|null, property: int|null}
     */
    private function filters(Request $request): array
    {
        return [
            'search' => trim((string) $request->string('search')),
            'status' => ApplicationStatus::tryFrom((string) $request->string('status')),
            'property' => $request->integer('property') ?: null,
        ];
    }

    /**
     * List every application across all units of a single property (newest first),
     * paginated — the per-property roll-up between the portfolio-wide list and a
     * single unit's applicants.
     */
    public function indexForProperty(Property $property): Response
    {
        $this->authorize('view', $property);

        $applications = Application::query()
            ->whereHas('unit', fn ($query) => $query->where('property_id', $property->id))
            ->with('unit')
            ->withCount('documents')
            ->latest('submitted_at')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Application $application): array => ApplicationRowResource::make($application)->resolve());

        return Inertia::render('screening/applicants/Property', [
            'property' => [
                'id' => $property->id,
                'name' => $property->name ?? $property->address_line1,
            ],
            'applications' => $applications,
        ]);
    }

    /**
     * List the applications submitted for a unit (newest first), paginated for
     * units that collect many applicants.
     */
    public function index(Unit $unit): Response
    {
        $this->authorize('view', $unit);

        $applications = $unit->applications()
            ->withCount('documents')
            ->latest('submitted_at')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Application $application): array => ApplicationRowResource::make(
                $application->setRelation('unit', $unit),
            )->resolve());

        return Inertia::render('screening/applicants/Index', [
            'property' => $unit->property,
            'unit' => $unit,
            'applications' => $applications,
        ]);
    }

    /**
     * Show a single application rendered from its immutable form snapshot, so later
     * edits to the unit's form never rewrite what the applicant actually submitted.
     */
    public function show(Application $application): Response
    {
        $this->authorize('view', $application);

        $application->load(['documents', 'unit.property', 'applicationLink']);

        return Inertia::render('screening/applicants/Show', [
            'property' => $application->unit->property,
            'unit' => $application->unit,
            'application' => $application,
            'source' => $application->applicationLink?->label,
            'documents' => $application->documents,
            'statuses' => array_map(
                fn (ApplicationStatus $status): array => ['value' => $status->value, 'label' => $status->label()],
                ApplicationStatus::cases(),
            ),
        ]);
    }

    /**
     * Update an application's status and the landlord's private notes. dwellow never
     * decides for the landlord — this is purely their manual review action.
     */
    public function update(UpdateApplicationRequest $request, Application $application): RedirectResponse
    {
        $this->authorize('update', $application);

        $application->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Application updated.')]);

        return back();
    }

    /**
     * Delete an application. Its document rows cascade with it, and the
     * Application delete event purges the stored files from the private disk.
     */
    public function destroy(Application $application): RedirectResponse
    {
        $this->authorize('delete', $application);

        $unit = $application->unit;

        $application->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Application deleted.')]);

        return to_route('units.applicants.index', $unit);
    }
}
