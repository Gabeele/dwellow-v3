<?php

namespace App\Screening;

use App\Enums\ApplicationStatus;
use App\Enums\FieldType;
use App\Http\Controllers\PublicScreeningController;
use App\Jobs\ScoreApplication;
use App\Mail\ApplicationReceivedMail;
use App\Models\Application;
use App\Models\ApplicationDraft;
use App\Models\ApplicationDraftDocument;
use App\Models\ApplicationLink;
use App\Notifications\NewApplicationNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/**
 * The application submission workflow, extracted from {@see PublicScreeningController}
 * so the controller stays thin (validate + delegate) and the business logic lives in one testable place.
 */
class ApplicationService
{
    /**
     * Persist a submitted application: snapshot the current form, store answers,
     * move uploaded documents onto the private disk, clear the applicant's draft,
     * and fire the applicant confirmation + landlord notification.
     *
     * Behaviour-preserving extraction of the controller's inline creation logic.
     * The `$answers` array is the request's validated `answers` (file fields still
     * carry their {@see UploadedFile} instances); `$draftCookie` is the per-link
     * draft cookie value used to resolve files uploaded in an earlier session.
     *
     * @param  array<string, mixed>  $answers
     */
    public function createApplication(ApplicationLink $link, array $answers, ?string $draftCookie): Application
    {
        // Only fields that were active at submit time render, validate, and snapshot.
        $fields = $link->unit->applicationFormOrDefault()->enabledFields();

        $fileFields = array_filter(
            $fields,
            fn (array $field): bool => ($field['type'] ?? null) === FieldType::File->value,
        );

        // A file may arrive inline (re-picked on this submit) or already live on
        // the applicant's draft from an earlier session. Drafts are resolved by
        // the same per-link cookie used throughout the resume flow.
        $draft = ApplicationDraft::forToken($link, $draftCookie);
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

        return $application;
    }

    /**
     * Queue the AI Score for a freshly-created application.
     *
     * Dispatched {@see ScoreApplication::dispatch()}->afterCommit() because the
     * database queue shares the application's connection — enqueuing inside the
     * surrounding transaction would race the worker against the not-yet-committed row.
     */
    public function requestScore(Application $application): void
    {
        ScoreApplication::dispatch($application)->afterCommit();
    }
}
