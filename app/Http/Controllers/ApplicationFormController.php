<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateApplicationFormRequest;
use App\Models\Unit;
use App\Screening\DefaultApplicationForm;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ApplicationFormController extends Controller
{
    /**
     * Show the form-builder page for a unit's application form.
     */
    public function edit(Unit $unit): Response
    {
        $form = $unit->applicationFormOrDefault();

        $this->authorize('view', $form);

        return Inertia::render('screening/forms/Edit', [
            'property' => $unit->property,
            'unit' => $unit,
            'sections' => $form->sections,
        ]);
    }

    /**
     * Persist which sections the landlord has chosen to include.
     *
     * The landlord only toggles whole sections; the server rebuilds the schema
     * from the canonical catalog so the stored form can never drift from it or
     * be tampered with field-by-field.
     */
    public function update(UpdateApplicationFormRequest $request, Unit $unit): RedirectResponse
    {
        $form = $unit->applicationFormOrDefault();

        $this->authorize('update', $form);

        $form->update([
            'sections' => DefaultApplicationForm::withEnabledSections(
                $request->validated()['enabled_sections'],
            ),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Application form updated.')]);

        return to_route('units.form.edit', $unit);
    }
}
