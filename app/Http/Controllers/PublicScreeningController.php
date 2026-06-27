<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Enums\FieldType;
use App\Http\Requests\StoreApplicationRequest;
use App\Mail\ApplicationReceivedMail;
use App\Models\Application;
use App\Models\ApplicationDraft;
use App\Models\ApplicationDraftDocument;
use App\Models\ApplicationLink;
use App\Notifications\NewApplicationNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
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
    public function store(StoreApplicationRequest $request, ApplicationLink $link): RedirectResponse
    {
        if (! $link->isOpen()) {
            return to_route('screening.show', $link->token);
        }

        // Silently drop automated submissions: show the bot the same success page
        // a human sees, but persist nothing.
        if ($request->isSpam()) {
            return to_route('screening.submitted', $link->token);
        }

        // Only fields that were active at submit time render, validate, and snapshot.
        $fields = $link->unit->applicationFormOrDefault()->enabledFields();
        $answers = $request->validated()['answers'] ?? [];

        $fileFields = array_filter(
            $fields,
            fn (array $field): bool => ($field['type'] ?? null) === FieldType::File->value,
        );

        // A file may arrive inline (re-picked on this submit) or already live on
        // the applicant's draft from an earlier session. Drafts are resolved by
        // the same per-link cookie used throughout the resume flow.
        $draft = ApplicationDraft::forToken($link, $request->cookie(ApplicationDraft::cookieName($link)));
        $draftDocs = $draft?->documents()->get()->keyBy('field_key') ?? collect();

        /** @var array<string, UploadedFile> $uploads */
        $uploads = [];
        /** @var array<string, ApplicationDraftDocument> $migrations */
        $migrations = [];

        foreach ($fileFields as $field) {
            $key = $field['key'];
            $value = $answers[$key] ?? null;

            if ($value instanceof UploadedFile) {
                $uploads[$key] = $value;
                // Record the filename in the answers; the file itself lives in a Document.
                $answers[$key] = $value->getClientOriginalName();
            } elseif ($draftDocs->has($key)) {
                $migrations[$key] = $draftDocs->get($key);
                $answers[$key] = $migrations[$key]->original_name;
            } else {
                $answers[$key] = null;
            }
        }

        // Persist the application and its documents atomically so a failure never
        // leaves an application with some of its uploads missing.
        $application = DB::transaction(function () use ($link, $answers, $fields, $uploads, $migrations): Application {
            $application = $link->applications()->make([
                'applicant_first_name' => (string) ($answers['first_name'] ?? ''),
                'applicant_last_name' => (string) ($answers['last_name'] ?? ''),
                'applicant_email' => (string) ($answers['email'] ?? ''),
                'applicant_phone' => (string) ($answers['phone'] ?? ''),
                'answers' => $answers,
                'form_snapshot' => $fields,
                'status' => ApplicationStatus::New,
                'submitted_at' => Carbon::now(),
            ]);

            // unit_id is denormalized and intentionally not mass-assignable.
            $application->unit_id = $link->unit_id;
            $application->save();

            foreach ($uploads as $key => $file) {
                $path = $file->store("applications/{$application->id}", 'local');

                $application->documents()->create([
                    'field_key' => $key,
                    'disk' => 'local',
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                ]);
            }

            // Move files the applicant uploaded in an earlier session onto the
            // application, reusing the already-stored blob rather than a re-upload.
            foreach ($migrations as $key => $document) {
                $path = "applications/{$application->id}/".basename($document->path);
                Storage::disk($document->disk)->move($document->path, $path);

                $application->documents()->create([
                    'field_key' => $key,
                    'disk' => $document->disk,
                    'path' => $path,
                    'original_name' => $document->original_name,
                    'mime_type' => $document->mime_type,
                    'size' => $document->size,
                ]);
            }

            return $application;
        });

        // The draft has served its purpose; deleting it clears any leftover
        // files (e.g. for fields disabled since upload) and forgetting the
        // cookie stops the now-gone draft from being looked up again.
        if ($draft !== null) {
            $draft->delete();
            Cookie::queue(Cookie::forget(ApplicationDraft::cookieName($link)));
        }

        $application->loadMissing('unit.property.landlord');

        if ($application->applicant_email !== '') {
            Mail::to($application->applicant_email)->send(
                new ApplicationReceivedMail($application),
            );
        }

        $application->unit->property->landlord?->notify(
            new NewApplicationNotification($application),
        );

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
