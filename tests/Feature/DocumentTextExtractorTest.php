<?php

use App\Models\Document;
use App\Screening\DocumentTextExtractor;
use App\Screening\PdfDocumentTextExtractor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| DocumentTextExtractor — PrinsFrank-backed implementation
|--------------------------------------------------------------------------
|
| Extracts model-ready text from applicant uploads: PDFs yield their text,
| anything else (images, Word docs, corrupt/empty files) yields the
| "unreadable" marker, and both per-document and combined length are capped.
|
*/

function storeFixturePdf(string $path): void
{
    Storage::disk('local')->put(
        $path,
        file_get_contents(base_path('tests/Fixtures/sample-application.pdf')),
    );
}

beforeEach(function () {
    Storage::fake('local');
});

it('extracts text from a PDF document', function () {
    storeFixturePdf('applications/1/app.pdf');

    $document = Document::factory()->make([
        'disk' => 'local',
        'path' => 'applications/1/app.pdf',
        'mime_type' => 'application/pdf',
    ]);

    $text = app(DocumentTextExtractor::class)->extract($document);

    expect($text)
        ->toContain('Rental Application - Jordan Tenant')
        ->toContain('Annual income: 78000 USD');
});

it('returns the unreadable marker for image-only documents', function () {
    Storage::disk('local')->put('applications/1/id.png', 'fake-image-bytes');

    $document = Document::factory()->make([
        'disk' => 'local',
        'path' => 'applications/1/id.png',
        'mime_type' => 'image/png',
    ]);

    $text = app(DocumentTextExtractor::class)->extract($document);

    expect($text)->toBe(DocumentTextExtractor::UNREADABLE_MARKER);
});

it('returns the unreadable marker for a missing or empty file', function () {
    $document = Document::factory()->make([
        'disk' => 'local',
        'path' => 'applications/1/gone.pdf',
        'mime_type' => 'application/pdf',
    ]);

    $text = app(DocumentTextExtractor::class)->extract($document);

    expect($text)->toBe(DocumentTextExtractor::UNREADABLE_MARKER);
});

it('caps the per-document length', function () {
    storeFixturePdf('applications/1/app.pdf');

    $document = Document::factory()->make([
        'disk' => 'local',
        'path' => 'applications/1/app.pdf',
        'mime_type' => 'application/pdf',
    ]);

    $text = (new PdfDocumentTextExtractor(perDocumentLimit: 20))->extract($document);

    expect(mb_strlen($text))->toBe(20);
});

it('concatenates many documents, labelling and capping the total', function () {
    storeFixturePdf('applications/1/app.pdf');

    $documents = collect([
        Document::factory()->make([
            'disk' => 'local',
            'path' => 'applications/1/app.pdf',
            'mime_type' => 'application/pdf',
            'original_name' => 'pay-stub.pdf',
        ]),
        Document::factory()->make([
            'disk' => 'local',
            'path' => 'applications/1/id.png',
            'mime_type' => 'image/png',
            'original_name' => 'photo-id.png',
        ]),
    ]);

    $text = app(DocumentTextExtractor::class)->extractFromMany($documents);

    expect($text)
        ->toContain('=== pay-stub.pdf ===')
        ->toContain('Rental Application - Jordan Tenant')
        ->toContain('=== photo-id.png ===')
        ->toContain(DocumentTextExtractor::UNREADABLE_MARKER);
});

it('caps the combined length across documents', function () {
    storeFixturePdf('applications/1/app.pdf');

    $documents = collect([
        Document::factory()->make([
            'disk' => 'local',
            'path' => 'applications/1/app.pdf',
            'mime_type' => 'application/pdf',
            'original_name' => 'a.pdf',
        ]),
        Document::factory()->make([
            'disk' => 'local',
            'path' => 'applications/1/app.pdf',
            'mime_type' => 'application/pdf',
            'original_name' => 'b.pdf',
        ]),
    ]);

    $text = (new PdfDocumentTextExtractor(totalLimit: 30))->extractFromMany($documents);

    expect(mb_strlen($text))->toBe(30);
});
