<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Http\Requests\UpdateApplicationRequest;
use App\Models\Application;
use App\Models\Property;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

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
        $user = $request->user();

        $search = trim((string) $request->string('search'));
        $status = ApplicationStatus::tryFrom((string) $request->string('status'));
        $propertyId = $request->integer('property') ?: null;

        $applications = Application::query()
            ->with('unit.property')
            ->withCount('documents')
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
            ->latest('submitted_at')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Application $application): array => [
                'id' => $application->id,
                'applicant_name' => trim("{$application->applicant_first_name} {$application->applicant_last_name}"),
                'applicant_email' => $application->applicant_email,
                'property_name' => $application->unit->property->name ?? $application->unit->property->address_line1,
                'unit_label' => $application->unit->label,
                'submitted_at' => $application->submitted_at,
                'status' => $application->status,
                'documents_count' => $application->documents_count,
                'url' => route('applicants.show', $application),
            ]);

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
                fn (ApplicationStatus $status): array => ['value' => $status->value, 'label' => $status->label()],
                ApplicationStatus::cases(),
            ),
            'filters' => [
                'search' => $search,
                'status' => $status?->value ?? '',
                'property' => $propertyId,
            ],
        ]);
    }

    /**
     * List the applications submitted for a unit (newest first).
     */
    public function index(Unit $unit): Response
    {
        $this->authorize('view', $unit);

        $applications = $unit->applications()
            ->withCount('documents')
            ->latest('submitted_at')
            ->get();

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

        $application->load(['documents', 'unit.property']);

        return Inertia::render('screening/applicants/Show', [
            'property' => $application->unit->property,
            'unit' => $application->unit,
            'application' => $application,
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
     * Delete an application along with the documents it stored on the private disk.
     * The document rows cascade with the application; their files must be removed by hand.
     */
    public function destroy(Application $application): RedirectResponse
    {
        $this->authorize('delete', $application);

        $unit = $application->unit;

        $application->load('documents');

        foreach ($application->documents as $document) {
            Storage::disk($document->disk)->delete($document->path);
        }

        $application->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Application deleted.')]);

        return to_route('units.applicants.index', $unit);
    }
}
