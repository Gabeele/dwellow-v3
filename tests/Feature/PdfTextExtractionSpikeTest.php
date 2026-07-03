<?php

use PrinsFrank\PdfParser\PdfParser;

/*
|--------------------------------------------------------------------------
| Milestone 0 spike — PDF text extraction with PrinsFrank/pdfparser
|--------------------------------------------------------------------------
|
| Goal: prove the approved dependency extracts sensible text from a real,
| committed PDF so the Milestone 2 DocumentTextExtractor can wrap it.
|
| Recorded API (prinsfrank/pdfparser ^3.1):
|   - From a file path:
|         (new PdfParser())->parseFile($path)->getText()
|   - From raw bytes (what we'll have off the storage disk):
|         (new PdfParser())->parseString($contents)->getText()
|   - getText() returns the whole document's text; pass a $pageSeparator
|     argument to control how pages are joined.
|
| Quality verdict: extraction on the fixture is clean — exact text, no
| garbling, line structure preserved. Good enough for v1; the
| DocumentTextExtractor interface (Milestone 2) keeps swapping cheap if a
| harder real-world PDF disappoints.
|
*/

function spikeFixturePdf(): string
{
    return base_path('tests/Fixtures/sample-application.pdf');
}

test('parseFile extracts sensible text from a real PDF', function () {
    $text = (new PdfParser)->parseFile(spikeFixturePdf())->getText();

    expect($text)
        ->toContain('Rental Application - Jordan Tenant')
        ->toContain('Annual income: 78000 USD')
        ->toContain('Employer: Northwind Logistics')
        ->toContain('two contactable references provided');
});

test('parseString extracts the same text from raw bytes', function () {
    $bytes = file_get_contents(spikeFixturePdf());

    $text = (new PdfParser)->parseString($bytes)->getText();

    expect($text)->toContain('Northwind Logistics');
});
