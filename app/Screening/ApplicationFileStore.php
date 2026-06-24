<?php

namespace App\Screening;

use App\Models\Application;
use Illuminate\Support\Facades\Storage;

class ApplicationFileStore
{
    /**
     * Permanently delete the private uploaded files for the given applications.
     *
     * Documents are stored per application under `applications/{id}` on the
     * private `local` disk. DB-level cascades remove the document *rows* without
     * firing model events, so file cleanup is centralized here and called from
     * every deletion path — the {@see Application} delete event and
     * the property/unit destroy flows — so deleted applicants' sensitive files
     * (IDs, pay stubs, self-reported credit reports) are never left on disk.
     */
    public static function purge(int ...$applicationIds): void
    {
        foreach ($applicationIds as $applicationId) {
            Storage::disk('local')->deleteDirectory("applications/{$applicationId}");
        }
    }
}
