<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreApplicationRequest;
use App\Models\ApplicationDraft;
use App\Models\ApplicationDraftDocument;
use App\Models\ApplicationLink;
use App\Screening\ApplicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicScreeningController extends Controller
{
    /**
     * Show the public application page for a shareable link, gating on the link's open state.
     *
     * Applicants have no account; the link is resolved by its unguessable token. When the link
     * is no longer open (revoked / expired / not accepting) a friendly closed state is rendered
     * instead of the form.
     */
    public function show(Request $request, ApplicationLink $link): Response
    {
        $link->load(['unit.property']);

        $isOpen = $link->isOpen();
        $unit = $link->unit;

        return Inertia::render('screening/Apply', [
            'isOpen' => $isOpen,
            'closedReason' => $isOpen ? null : $link->closedReason(),
            'unit' => $this->unitPayload($link),
            // Resolve-or-default so a unit provisioned before the form observer
            // existed still shows the standard application instead of a blank page.
            'sections' => $isOpen ? $unit->applicationFormOrDefault()->enabledSections() : [],
            // Rehydrate an in-progress draft (cookie-resolved) so the applicant
            // resumes where they left off. Only meaningful while the link is open.
            'draft' => $isOpen ? $this->draftPayload($request, $link) : null,
        ]);
    }

    /**
     * Persist a submitted application: snapshot the current form, store answers,
     * and move any uploaded documents onto the private disk.
     *
     * Validation is driven by the unit's current schema (see StoreApplicationRequest).
     * A link that closed after the applicant opened the page sends them to the
     * friendly closed state instead of erroring.
     */
    public function store(StoreApplicationRequest $request, ApplicationLink $link, ApplicationService $applications): RedirectResponse
    {
        if (! $link->isOpen()) {
            return to_route('screening.show', $link->token);
        }

        // Silently drop automated submissions: show the bot the same success page
        // a human sees, but persist nothing.
        if ($request->isSpam()) {
            return to_route('screening.submitted', $link->token);
        }

        $application = $applications->createApplication(
            $link,
            $request->validated()['answers'] ?? [],
            $request->cookie(ApplicationDraft::cookieName($link)),
        );

        // Queue the AI Score; dispatched after commit so the worker never races
        // the not-yet-committed application row on the shared database queue.
        $applications->requestScore($application);

        return to_route('screening.submitted', $link->token)
            ->with('reference', $application->public_id);
    }

    /**
     * The cookie-resolved draft for a link, shaped for the Apply page, or null.
     *
     * Files are listed by name/size only — the browser can't be handed back the
     * bytes, so the page shows them as already-attached and the actual files are
     * pulled from the draft at submit time.
     *
     * @return array{
     *     answers: array<string, mixed>,
     *     current_step: int,
     *     files: array<int, array{field_key: string, original_name: string, size: int|null}>,
     * }|null
     */
    private function draftPayload(Request $request, ApplicationLink $link): ?array
    {
        $draft = ApplicationDraft::forToken($link, $request->cookie(ApplicationDraft::cookieName($link)));

        if ($draft === null) {
            return null;
        }

        return [
            'answers' => $draft->answers ?? [],
            'current_step' => $draft->current_step,
            'files' => $draft->documents()->get()
                ->map(fn (ApplicationDraftDocument $document): array => [
                    'field_key' => $document->field_key,
                    'original_name' => $document->original_name,
                    'size' => $document->size,
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * Render the post-submission thank-you page for a link.
     */
    public function submitted(ApplicationLink $link): Response
    {
        $link->load('unit.property');

        return Inertia::render('screening/Submitted', [
            'unit' => $this->unitPayload($link),
            'reference' => session('reference'),
        ]);
    }

    /**
     * The unit + address payload shared by the public screening pages.
     *
     * @return array{
     *     label: string,
     *     address: array{
     *         line1: string,
     *         line2: string|null,
     *         city: string,
     *         region: string,
     *         postal_code: string,
     *         country: string,
     *     },
     * }
     */
    private function unitPayload(ApplicationLink $link): array
    {
        $unit = $link->unit;
        $property = $unit->property;

        return [
            'label' => $unit->label,
            'address' => [
                'line1' => $property->address_line1,
                'line2' => $property->address_line2,
                'city' => $property->city,
                'region' => $property->region,
                'postal_code' => $property->postal_code,
                'country' => $property->country,
            ],
        ];
    }
}
