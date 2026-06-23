<?php

namespace App\Http\Controllers;

use App\Enums\OccupancyStatus;
use App\Enums\PropertyType;
use App\Enums\RentalType;
use App\Http\Requests\StorePropertyRequest;
use App\Http\Requests\UpdatePropertyRequest;
use App\Models\Property;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PropertyController extends Controller
{
    /**
     * List the current landlord's properties.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Property::class);

        $properties = $request->user()
            ->properties()
            ->withCount([
                'units',
                'units as occupied_units_count' => fn ($query) => $query->where('status', OccupancyStatus::Occupied),
                'units as available_units_count' => fn ($query) => $query->where('status', OccupancyStatus::Available),
            ])
            ->latest()
            ->get();

        return Inertia::render('properties/Index', [
            'properties' => $properties,
        ]);
    }

    /**
     * Show the form for creating a property.
     */
    public function create(): Response
    {
        $this->authorize('create', Property::class);

        return Inertia::render('properties/Create', [
            'options' => $this->formOptions(),
        ]);
    }

    /**
     * Store a newly created property.
     */
    public function store(StorePropertyRequest $request): RedirectResponse
    {
        $this->authorize('create', Property::class);

        $property = $request->user()->properties()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Property created.')]);

        return to_route('properties.show', $property);
    }

    /**
     * Show a single property and its units.
     */
    public function show(Property $property): Response
    {
        $this->authorize('view', $property);

        $property->load('units');

        return Inertia::render('properties/Show', [
            'property' => $property,
        ]);
    }

    /**
     * Show the form for editing a property.
     */
    public function edit(Property $property): Response
    {
        $this->authorize('update', $property);

        return Inertia::render('properties/Edit', [
            'property' => $property,
            'options' => $this->formOptions(),
        ]);
    }

    /**
     * Update the given property.
     */
    public function update(UpdatePropertyRequest $request, Property $property): RedirectResponse
    {
        $this->authorize('update', $property);

        $property->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Property updated.')]);

        return to_route('properties.show', $property);
    }

    /**
     * Delete the given property.
     */
    public function destroy(Property $property): RedirectResponse
    {
        $this->authorize('delete', $property);

        $property->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Property deleted.')]);

        return to_route('properties.index');
    }

    /**
     * Enum options used to populate the property form selects.
     *
     * @return array<string, array<int, array{value: string, label: string}>>
     */
    private function formOptions(): array
    {
        return [
            'types' => array_map(fn (PropertyType $type) => ['value' => $type->value, 'label' => $type->label()], PropertyType::cases()),
            'rentalTypes' => array_map(fn (RentalType $type) => ['value' => $type->value, 'label' => $type->label()], RentalType::cases()),
            'statuses' => array_map(fn (OccupancyStatus $status) => ['value' => $status->value, 'label' => $status->label()], OccupancyStatus::cases()),
        ];
    }
}
