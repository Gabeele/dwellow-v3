<?php

namespace App\Screening;

use App\Models\Document;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PrinsFrank\PdfParser\PdfParser;
use Throwable;

/**
 * {@see DocumentTextExtractor} backed by `prinsfrank/pdfparser`.
 *
 * Only PDFs are text-extractable in v1; image-only scans and Word documents
 * return the {@see DocumentTextExtractor::UNREADABLE_MARKER} (no OCR — see the
 * scoring-engine plan). Length is capped per document and across the combined
 * set so a large or many-page upload cannot blow the model's context window.
 */
class PdfDocumentTextExtractor implements DocumentTextExtractor
{
    public const DEFAULT_PER_DOCUMENT_LIMIT = 10_000;

    public const DEFAULT_TOTAL_LIMIT = 25_000;

    public function __construct(
        private readonly int $perDocumentLimit = self::DEFAULT_PER_DOCUMENT_LIMIT,
        private readonly int $totalLimit = self::DEFAULT_TOTAL_LIMIT,
    ) {}

    public function extract(Document $document): string
    {
        if ($document->mime_type !== 'application/pdf') {
            return self::UNREADABLE_MARKER;
        }

        $bytes = Storage::disk($document->disk)->get($document->path);

        if ($bytes === null || $bytes === '') {
            return self::UNREADABLE_MARKER;
        }

        try {
            $text = (new PdfParser)->parseString($bytes)->getText();
        } catch (Throwable) {
            return self::UNREADABLE_MARKER;
        }

        $text = trim($text);

        if ($text === '') {
            return self::UNREADABLE_MARKER;
        }

        return Str::limit($text, $this->perDocumentLimit, '');
    }

    public function extractFromMany(iterable $documents): string
    {
        $sections = [];

        foreach ($documents as $document) {
            $sections[] = "=== {$document->original_name} ===\n".$this->extract($document);
        }

        return Str::limit(implode("\n\n", $sections), $this->totalLimit, '');
    }
}
