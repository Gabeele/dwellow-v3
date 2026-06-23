<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Unit;
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
        ]);
    }
}
