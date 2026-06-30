<?php

namespace App\Screening;

use App\Models\Document;

/**
 * Extracts the readable text from applicant-uploaded {@see Document}s so the
 * scoring engine can feed it to the model alongside the form answers.
 *
 * Implementations cap both per-document and combined length (model context is
 * finite) and return a human-readable "unreadable" marker for files we cannot
 * pull text from (image-only scans, Word docs) rather than throwing — a single
 * bad upload must never fail the whole Score.
 */
interface DocumentTextExtractor
{
    /**
     * Marker returned in place of text for files we cannot read.
     */
    public const UNREADABLE_MARKER = '[unreadable: no extractable text]';

    /**
     * Extract capped, readable text from a single document.
     *
     * Returns {@see self::UNREADABLE_MARKER} when the file has no extractable
     * text (image-only, unsupported type, empty, or corrupt).
     */
    public function extract(Document $document): string;

    /**
     * Extract and concatenate text from many documents, labelled by file and
     * capped to a combined total length.
     *
     * @param  iterable<Document>  $documents
     */
    public function extractFromMany(iterable $documents): string;
}
