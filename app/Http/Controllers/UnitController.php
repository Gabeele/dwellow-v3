<?php

namespace App\Http\Controllers;

use App\Enums\OccupancyStatus;
use App\Http\Requests\StoreUnitRequest;
use App\Http\Requests\UpdateUnitRequest;
use App\Models\Property;
use App\Models\Unit;
use App\Screening\ApplicationFileStore;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class UnitController extends Controller
{
    /**
     * Show the form for adding a unit to a property.
     */
    public function create(Property $property): Response
    {
        $this->authorize('update', $property);

        return Inertia::render('properties/units/Create', [
            'property' => $property,
            'statuses' => $this->statusOptions(),
        ]);
    }

    /**
     * Store a new unit under the given property.
     */
    public function store(StoreUnitRequest $request, Property $property): RedirectResponse
    {
        $this->authorize('update', $property);

        $property->units()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Unit added.')]);

        return to_route('properties.show', $property);
    }

    /**
     * Show the form for editing a unit.
     */
    public function edit(Unit $unit): Response
    {
        $this->authorize('update', $unit);

        return Inertia::render('properties/units/Edit', [
            'property' => $unit->property,
            'unit' => $unit,
            'statuses' => $this->statusOptions(),
        ]);
    }

    /**
     * Update the given unit.
     */
    public function update(UpdateUnitRequest $request, Unit $unit): RedirectResponse
    {
        $this->authorize('update', $unit);

        $unit->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Unit updated.')]);

        return to_route('properties.show', $unit->property);
    }

    /**
     * Delete the given unit.
     */
    public function destroy(Unit $unit): RedirectResponse
    {
        $this->authorize('delete', $unit);

        $property = $unit->property;

        // Capture application ids before the cascade removes their rows, so their
        // stored files can be purged from the private disk.
        $applicationIds = $unit->applications()->pluck('id')->all();

        $unit->delete();

        ApplicationFileStore::purge(...$applicationIds);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Unit deleted.')]);

        return to_route('properties.show', $property);
    }

    /**
     * Occupancy status options for the unit form select.
     *
     * @return array<int, array{value: string, label: string}>
     */
    private function statusOptions(): array
    {
        return array_map(
            fn (OccupancyStatus $status) => ['value' => $status->value, 'label' => $status->label()],
            OccupancyStatus::cases(),
        );
    }
}
