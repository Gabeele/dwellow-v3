<?php

namespace App\Http\Controllers;

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
}
