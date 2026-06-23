<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Http\Requests\UpdateApplicationRequest;
use App\Models\Application;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ApplicationController extends Controller
{
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
}
