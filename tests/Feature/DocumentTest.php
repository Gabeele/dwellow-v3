<?php

use App\Models\Application;
use App\Models\Document;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('belongs to an application', function () {
    $document = Document::factory()->create();

    expect($document->application)->toBeInstanceOf(Application::class);
});

it('persists its columns', function () {
    $application = Application::factory()->create();

    $document = Document::factory()->create([
        'application_id' => $application->id,
        'field_key' => 'photo_id',
        'disk' => 'local',
        'path' => 'applications/abc/id.pdf',
        'original_name' => 'id.pdf',
        'mime_type' => 'application/pdf',
        'size' => 2048,
    ]);

    $fresh = $document->fresh();

    expect($fresh->application_id)->toBe($application->id)
        ->and($fresh->field_key)->toBe('photo_id')
        ->and($fresh->disk)->toBe('local')
        ->and($fresh->path)->toBe('applications/abc/id.pdf')
        ->and($fresh->original_name)->toBe('id.pdf')
        ->and($fresh->mime_type)->toBe('application/pdf')
        ->and($fresh->size)->toBe(2048);
});

it('is included in an application\'s documents', function () {
    $application = Application::factory()->create();
    $document = Document::factory()->create(['application_id' => $application->id]);

    expect($application->documents()->pluck('id'))->toContain($document->id);
});
