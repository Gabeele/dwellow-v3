<?php

namespace App\Http\Controllers;

use App\Enums\FieldType;
use App\Http\Requests\SaveDraftRequest;
use App\Http\Requests\StoreDraftFileRequest;
use App\Models\ApplicationDraft;
use App\Models\ApplicationLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Storage;

/**
 * Mutations for an applicant's in-progress draft: autosaved answers and the
 * files uploaded as they go. All endpoints are account-free and identify the
 * draft by an unguessable, per-link cookie. Closed links are quiet no-ops so a
 * link that closes mid-application never errors in the applicant's face.
 */
class PublicScreeningDraftController extends Controller
{
    /**
     * How long a draft cookie lives, in minutes (mirrors the prune window).
     */
    private const int COOKIE_MINUTES = 60 * 24 * 30;

    /**
     * Autosave the applicant's text answers and wizard position.
     */
    public function save(SaveDraftRequest $request, ApplicationLink $link): Response
    {
        if (! $link->isOpen()) {
            return response()->noContent();
        }

        $draft = $this->resolveOrCreateDraft($request, $link);

        // Keep only answers for fields that still exist and aren't files —
        // files live in draft documents, never in the answers blob.
        $allowedKeys = collect($link->unit->applicationFormOrDefault()->enabledFields())
            ->reject(fn (array $field): bool => ($field['type'] ?? null) === FieldType::File->value)
            ->pluck('key')
            ->all();

        $draft->update([
            'answers' => Arr::only($request->validated()['answers'] ?? [], $allowedKeys),
            'current_step' => (int) $request->validated()['current_step'],
        ]);

        return response()->noContent();
    }

    /**
     * Upload (or replace) a single file against the draft for one form field.
     */
    public function storeFile(StoreDraftFileRequest $request, ApplicationLink $link, string $fieldKey): JsonResponse|Response
    {
        if (! $link->isOpen()) {
            return response()->noContent();
        }

        // Only accept files for fields that are actually file fields on the
        // current form, so the upload can't be aimed at an arbitrary key.
        if (! $this->isFileField($link, $fieldKey)) {
            abort(404);
        }

        $draft = $this->resolveOrCreateDraft($request, $link);

        // Replace any previous file for this field: drop the old document + blob first.
        foreach ($draft->documents()->where('field_key', $fieldKey)->get() as $existing) {
            Storage::disk($existing->disk)->delete($existing->path);
            $existing->delete();
        }

        $file = $request->file('file');
        $path = $file->store("application-drafts/{$draft->id}", 'local');

        $draft->documents()->create([
            'field_key' => $fieldKey,
            'disk' => 'local',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
        ]);

        $draft->touch();

        return response()->json([
            'original_name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
        ]);
    }

    /**
     * Remove the draft's file for one field (the applicant tapped "Remove").
     */
    public function destroyFile(Request $request, ApplicationLink $link, string $fieldKey): Response
    {
        $draft = ApplicationDraft::forToken($link, $request->cookie(ApplicationDraft::cookieName($link)));

        if ($draft !== null) {
            foreach ($draft->documents()->where('field_key', $fieldKey)->get() as $existing) {
                Storage::disk($existing->disk)->delete($existing->path);
                $existing->delete();
            }

            $draft->touch();
        }

        return response()->noContent();
    }

    /**
     * Find the cookie's draft for this link, creating one (and queueing the
     * cookie) the first time the applicant saves anything.
     */
    private function resolveOrCreateDraft(Request $request, ApplicationLink $link): ApplicationDraft
    {
        $cookieName = ApplicationDraft::cookieName($link);
        $draft = ApplicationDraft::forToken($link, $request->cookie($cookieName));

        if ($draft === null) {
            $draft = new ApplicationDraft(['answers' => [], 'current_step' => 0]);
            $draft->application_link_id = $link->getKey();
            $draft->save();
        }

        // Re-queue each save so an active applicant's cookie keeps sliding forward.
        Cookie::queue($cookieName, $draft->token, self::COOKIE_MINUTES);

        return $draft;
    }

    /**
     * Whether the given key is a file field on the link's current form.
     */
    private function isFileField(ApplicationLink $link, string $fieldKey): bool
    {
        return collect($link->unit->applicationFormOrDefault()->enabledFields())
            ->contains(fn (array $field): bool => ($field['key'] ?? null) === $fieldKey
                && ($field['type'] ?? null) === FieldType::File->value);
    }
}
