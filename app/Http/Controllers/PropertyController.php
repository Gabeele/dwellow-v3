<?php

namespace App\Http\Controllers;

use App\Enums\OccupancyStatus;
use App\Enums\PropertyType;
use App\Enums\RentalType;
use App\Http\Requests\StorePropertyRequest;
use App\Http\Requests\UpdatePropertyRequest;
use App\Models\Application;
use App\Models\Property;
use App\Models\Unit;
use App\Screening\ApplicationFileStore;
use App\Screening\BackingUnitProvisioner;
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
            ->withUnitCounts()
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

        // Legacy/edge-case whole rentals can have zero units, which would leave
        // the screening surface with nothing to attach to. Heal it here so the
        // screening surface always exists. Idempotent and a no-op otherwise.
        if (BackingUnitProvisioner::applies($property)) {
            BackingUnitProvisioner::ensure($property);
        }

        $property->load([
            'units' => fn ($query) => $query
                ->withCount('applications')
                ->with(['applicationLink' => fn ($link) => $link->withCount('applications')]),
        ]);

        // Each unit shares a single link. Heal any unit missing one (legacy units
        // predate auto-provisioning), then expose its public applicant URL so the
        // landlord can copy and share it.
        $property->units->each(function (Unit $unit): void {
            $link = $unit->applicationLink ?? $unit->applicationLinkOrDefault()->loadCount('applications');
            $link->setAttribute('public_url', url('/screening/'.$link->token));
            $unit->setRelation('applicationLink', $link);
        });

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

        // Capture descendant application ids before the cascade removes their
        // rows, so their stored files can be purged from the private disk.
        $applicationIds = Application::query()
            ->whereHas('unit', fn ($query) => $query->where('property_id', $property->id))
            ->pluck('id')
            ->all();

        $property->delete();

        ApplicationFileStore::purge(...$applicationIds);

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
