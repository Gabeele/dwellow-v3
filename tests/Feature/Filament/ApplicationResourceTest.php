<?php

use App\Enums\ApplicationStatus;
use App\Filament\Resources\Applications\ApplicationResource;
use App\Filament\Resources\Applications\Pages\EditApplication;
use App\Filament\Resources\Applications\Pages\ListApplications;
use App\Filament\Resources\Applications\Pages\ViewApplication;
use App\Filament\Resources\Applications\RelationManagers\DocumentsRelationManager;
use App\Models\Application;
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

it('lists applications for an admin', function () {
    $application = Application::factory()->create([
        'applicant_first_name' => 'Rosa',
        'applicant_last_name' => 'Lin',
    ]);

    Livewire::test(ListApplications::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$application])
        ->assertSee('Rosa Lin');
});

it('filters applications by status', function () {
    $approved = Application::factory()->create(['status' => ApplicationStatus::Approved]);
    $new = Application::factory()->create(['status' => ApplicationStatus::New]);

    Livewire::test(ListApplications::class)
        ->filterTable('status', ApplicationStatus::Approved->value)
        ->assertCanSeeTableRecords([$approved])
        ->assertCanNotSeeTableRecords([$new]);
});

it('shows an application detail page for an admin', function () {
    $application = Application::factory()->create(['applicant_email' => 'rosa@example.com']);

    Livewire::test(ViewApplication::class, ['record' => $application->id])
        ->assertOk()
        ->assertSee('rosa@example.com');
});

it('lets an admin update an application status and notes', function () {
    $application = Application::factory()->create(['status' => ApplicationStatus::New]);

    Livewire::test(EditApplication::class, ['record' => $application->id])
        ->fillForm([
            'status' => ApplicationStatus::Reviewing->value,
            'landlord_notes' => 'Following up on references.',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($application->fresh())
        ->status->toBe(ApplicationStatus::Reviewing)
        ->landlord_notes->toBe('Following up on references.');
});

it('cannot create applications from the admin panel', function () {
    expect(ApplicationResource::canCreate())->toBeFalse();
});

it('lists an applications documents in the relation manager', function () {
    $application = Application::factory()->create();
    $document = Document::factory()->for($application)->create(['original_name' => 'pay-stub.pdf']);

    Livewire::test(DocumentsRelationManager::class, [
        'ownerRecord' => $application,
        'pageClass' => ViewApplication::class,
    ])
        ->assertOk()
        ->assertCanSeeTableRecords([$document])
        ->assertSee('pay-stub.pdf');
});

it('streams a document download from the relation manager', function () {
    Storage::fake('local');

    $application = Application::factory()->create();
    $document = Document::factory()->for($application)->create([
        'disk' => 'local',
        'path' => 'applications/sample/id.pdf',
        'original_name' => 'id.pdf',
    ]);
    Storage::disk('local')->put($document->path, 'fake-bytes');

    Livewire::test(DocumentsRelationManager::class, [
        'ownerRecord' => $application,
        'pageClass' => ViewApplication::class,
    ])
        ->callAction(TestAction::make('download')->table($document))
        ->assertFileDownloaded('id.pdf');
});
