<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateApplicationLinkRequest;
use App\Models\ApplicationLink;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ApplicationLinkController extends Controller
{
    /**
     * Toggle whether a unit's application link is accepting submissions.
     *
     * Each unit has exactly one link, provisioned with the unit, so there is
     * nothing to create or revoke — only this on/off switch.
     */
    public function update(UpdateApplicationLinkRequest $request, ApplicationLink $link): RedirectResponse
    {
        $this->authorize('update', $link);

        $link->update($request->validated());

        $message = $link->is_accepting
            ? __('Application link turned on.')
            : __('Application link turned off.');

        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return back();
    }
}
