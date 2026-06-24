<?php

use App\Filament\Resources\Documents\DocumentResource;
use App\Filament\Resources\Documents\Pages\ListDocuments;
use App\Filament\Resources\Documents\Pages\ViewDocument;
use App\Models\Document;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->actingAs(User::factory()->admin()->create());
});

it('lists documents for an admin', function () {
    $document = Document::factory()->create(['original_name' => 'lease-application.pdf']);

    Livewire::test(ListDocuments::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$document])
        ->assertSee('lease-application.pdf');
});

it('shows a document detail page for an admin', function () {
    $document = Document::factory()->create(['original_name' => 'lease-application.pdf']);

    Livewire::test(ViewDocument::class, ['record' => $document->id])
        ->assertOk()
        ->assertSee('lease-application.pdf');
});

it('streams a document download from the table', function () {
    Storage::fake('local');

    $document = Document::factory()->create([
        'disk' => 'local',
        'path' => 'applications/sample/proof.pdf',
        'original_name' => 'proof.pdf',
    ]);
    Storage::disk('local')->put($document->path, 'fake-bytes');

    Livewire::test(ListDocuments::class)
        ->callAction(TestAction::make('download')->table($document))
        ->assertFileDownloaded('proof.pdf');
});

it('cannot create or edit documents from the admin panel', function () {
    $document = Document::factory()->create();

    expect(DocumentResource::canCreate())->toBeFalse()
        ->and(DocumentResource::canEdit($document))->toBeFalse();
});
