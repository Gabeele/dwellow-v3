<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreApplicationLinkRequest;
use App\Http\Requests\UpdateApplicationLinkRequest;
use App\Models\ApplicationLink;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class ApplicationLinkController extends Controller
{
    /**
     * Create a new shareable application link for the given unit.
     */
    public function store(StoreApplicationLinkRequest $request, Unit $unit): RedirectResponse
    {
        $this->authorize('create', [ApplicationLink::class, $unit]);

        $unit->applicationLinks()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Application link created.')]);

        return back();
    }

    /**
     * Toggle accepting and set/clear the expiry of an application link.
     */
    public function update(UpdateApplicationLinkRequest $request, ApplicationLink $link): RedirectResponse
    {
        $this->authorize('update', $link);

        $link->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Application link updated.')]);

        return back();
    }

    /**
     * Revoke an application link (soft revoke so historical applications keep their link).
     */
    public function destroy(ApplicationLink $link): RedirectResponse
    {
        $this->authorize('delete', $link);

        $link->update(['revoked_at' => Carbon::now()]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Application link revoked.')]);

        return back();
    }
}
