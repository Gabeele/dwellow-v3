<?php

use App\Models\Application;
use App\Models\ApplicationLink;
use App\Models\Document;
use App\Models\Property;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

/**
 * Create a document stored on the private disk, owned by the given landlord.
 */
function documentOwnedBy(User $landlord): Document
{
    $property = Property::factory()->for($landlord, 'landlord')->create();
    $unit = Unit::factory()->for($property)->create();
    $link = ApplicationLink::factory()->for($unit)->create();
    $application = Application::factory()->for($link, 'applicationLink')->create();

    $path = "applications/{$application->id}/id.png";
    Storage::disk('local')->put($path, 'fake-bytes');

    return Document::factory()->for($application)->create([
        'disk' => 'local',
        'path' => $path,
        'original_name' => 'photo-id.png',
    ]);
}

test('the owning landlord can download a document', function () {
    Storage::fake('local');

    $landlord = User::factory()->landlord()->create();
    $document = documentOwnedBy($landlord);

    $response = $this->actingAs($landlord)
        ->get(route('documents.download', $document));

    $response->assertOk();
    expect($response->headers->get('content-disposition'))
        ->toContain('photo-id.png');
});

test('a different landlord cannot download the document', function () {
    Storage::fake('local');

    $landlord = User::factory()->landlord()->create();
    $document = documentOwnedBy($landlord);

    $this->actingAs(User::factory()->landlord()->create())
        ->get(route('documents.download', $document))
        ->assertForbidden();
});
