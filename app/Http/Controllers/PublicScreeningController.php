<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Enums\FieldType;
use App\Http\Requests\StoreApplicationRequest;
use App\Mail\ApplicationReceivedMail;
use App\Models\ApplicationLink;
use App\Notifications\NewApplicationNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
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
    public function show(ApplicationLink $link): Response
    {
        $link->load(['unit.property', 'unit.applicationForm']);

        $isOpen = $link->isOpen();
        $unit = $link->unit;

        return Inertia::render('screening/Apply', [
            'isOpen' => $isOpen,
            'closedReason' => $isOpen ? null : $link->closedReason(),
            'unit' => $this->unitPayload($link),
            'sections' => $isOpen ? ($unit->applicationForm?->enabledSections() ?? []) : [],
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

        $link->load('unit.applicationForm');

        // Only fields that were active at submit time render, validate, and snapshot.
        $fields = $link->unit->applicationForm?->enabledFields() ?? [];
        $answers = $request->validated()['answers'] ?? [];

        $fileFields = array_filter(
            $fields,
            fn (array $field): bool => ($field['type'] ?? null) === FieldType::File->value,
        );

        /** @var array<string, UploadedFile> $uploads */
        $uploads = [];

        foreach ($fileFields as $field) {
            $key = $field['key'];
            $value = $answers[$key] ?? null;

            if ($value instanceof UploadedFile) {
                $uploads[$key] = $value;
                // Record the filename in the answers; the file itself lives in a Document.
                $answers[$key] = $value->getClientOriginalName();
            } else {
                $answers[$key] = null;
            }
        }

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
     * @return array<string, mixed>
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
