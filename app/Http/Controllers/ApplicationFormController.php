<?php

namespace App\Http\Controllers;

use App\Enums\FieldType;
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
        $form = $unit->applicationForm()->firstOrCreate([], [
            'fields' => DefaultApplicationForm::fields(),
        ]);

        $this->authorize('view', $form);

        return Inertia::render('screening/forms/Edit', [
            'property' => $unit->property,
            'unit' => $unit,
            'fields' => $form->fields,
            'fieldTypes' => $this->fieldTypeOptions(),
            'defaultFields' => DefaultApplicationForm::fields(),
        ]);
    }

    /**
     * Persist the edited application-form schema for a unit.
     */
    public function update(UpdateApplicationFormRequest $request, Unit $unit): RedirectResponse
    {
        $form = $unit->applicationForm()->firstOrCreate([], [
            'fields' => DefaultApplicationForm::fields(),
        ]);

        $this->authorize('update', $form);

        $form->update(['fields' => $request->validated()['fields']]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Application form updated.')]);

        return to_route('units.form.edit', $unit);
    }

    /**
     * Field-type options for the form-builder select.
     *
     * @return array<int, array{value: string, label: string, expectsOptions: bool, isFileUpload: bool}>
     */
    private function fieldTypeOptions(): array
    {
        return array_map(
            fn (FieldType $type) => [
                'value' => $type->value,
                'label' => $type->label(),
                'expectsOptions' => $type->expectsOptions(),
                'isFileUpload' => $type->isFileUpload(),
            ],
            FieldType::cases(),
        );
    }
}
