<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Http\Requests\ApproveApplicationRequest;
use App\Http\Requests\RejectApplicationRequest;
use App\Http\Requests\UpdateApplicationRequest;
use App\Http\Resources\ApplicationRowResource;
use App\Mail\ApplicationApprovedMail;
use App\Mail\ApplicationRejectedMail;
use App\Models\Application;
use App\Models\Property;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
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

        $application->load(['documents', 'unit.property', 'score', 'scoreAgent']);

        return Inertia::render('screening/applicants/Show', [
            'property' => $application->unit->property,
            'unit' => $application->unit,
            'application' => $application,
            'documents' => $application->documents,
            'statuses' => array_map(
                fn (ApplicationStatus $status): array => ['value' => $status->value, 'label' => $status->label()],
                ApplicationStatus::cases(),
            ),
            // The AI Score and the status of the agent run that produces it. The
            // status drives the processing/failed/ready states on the detail
            // page; the score payload is only present once the run completes.
            'scoreStatus' => $application->scoreAgent?->status->value,
            'score' => $this->scorePayload($application),
            // How many other applicants for this unit are still awaiting a
            // decision — drives the "decline the others" option when approving.
            'otherActiveCount' => $this->applicationsAwaitingDecision($application)->count(),
        ]);
    }

    /**
     * Shape the application's Score for the detail page, or null while no Score
     * has been produced yet (still processing, or the agent run failed).
     *
     * @return array{fit_score: int|null, score_rationale: string|null, summary: string|null, red_flags: array<int, string>, strengths: array<int, string>}|null
     */
    private function scorePayload(Application $application): ?array
    {
        $score = $application->score;

        if ($score === null) {
            return null;
        }

        return [
            'fit_score' => $score->fit_score,
            'score_rationale' => $score->score_rationale,
            'summary' => $score->summary,
            'red_flags' => $score->red_flags ?? [],
            'strengths' => $score->strengths ?? [],
        ];
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
     * Approve an application. Optionally emails the applicant, and optionally
     * declines the other applicants still in the running for the same unit —
     * emailing them too if asked. Each side effect is a toggle the landlord
     * chose in the confirmation dialog; nothing happens unless they opt in.
     */
    public function approve(ApproveApplicationRequest $request, Application $application): RedirectResponse
    {
        $this->authorize('update', $application);

        $application->load('unit.property');
        $application->update(['status' => ApplicationStatus::Approved]);

        if ($request->boolean('notify_applicant')) {
            Mail::to($application->applicant_email)->send(new ApplicationApprovedMail($application));
        }

        $declinedCount = 0;

        if ($request->boolean('decline_others')) {
            $others = $this->applicationsAwaitingDecision($application)->with('unit.property')->get();

            foreach ($others as $other) {
                $other->update(['status' => ApplicationStatus::Rejected]);

                if ($request->boolean('notify_declined')) {
                    Mail::to($other->applicant_email)->send(new ApplicationRejectedMail($other));
                }
            }

            $declinedCount = $others->count();
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $declinedCount > 0
                ? __('Application approved.').' '.trans_choice(
                    ':count other applicant was declined.|:count other applicants were declined.',
                    $declinedCount,
                    ['count' => $declinedCount],
                )
                : __('Application approved.'),
        ]);

        return back();
    }

    /**
     * Decline an application, optionally emailing the applicant. The landlord
     * confirms this in a dialog before it runs.
     */
    public function reject(RejectApplicationRequest $request, Application $application): RedirectResponse
    {
        $this->authorize('update', $application);

        $application->load('unit.property');
        $application->update(['status' => ApplicationStatus::Rejected]);

        if ($request->boolean('notify_applicant')) {
            Mail::to($application->applicant_email)->send(new ApplicationRejectedMail($application));
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Application declined.')]);

        return back();
    }

    /**
     * The other applications for this one's unit that are still awaiting a
     * decision (New or Reviewing) — the cohort affected when it is approved.
     *
     * @return HasMany<Application, Unit>
     */
    private function applicationsAwaitingDecision(Application $application): HasMany
    {
        return $application->unit->applications()
            ->whereKeyNot($application->getKey())
            ->whereIn('status', [ApplicationStatus::New, ApplicationStatus::Reviewing]);
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
