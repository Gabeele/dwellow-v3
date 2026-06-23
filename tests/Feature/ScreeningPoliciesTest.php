<?php

use App\Models\Application;
use App\Models\ApplicationLink;
use App\Models\Document;
use App\Models\Property;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Build a unit owned by the given landlord.
 */
function unitOwnedBy(User $landlord): Unit
{
    $property = Property::factory()->create(['landlord_id' => $landlord->id]);

    return Unit::factory()->create(['property_id' => $property->id]);
}

beforeEach(function () {
    $this->owner = User::factory()->landlord()->create();
    $this->stranger = User::factory()->landlord()->create();
    $this->unit = unitOwnedBy($this->owner);
});

it('lets the owning landlord view and update an application form but denies a stranger', function () {
    $form = $this->unit->applicationForm;

    expect($this->owner->can('view', $form))->toBeTrue()
        ->and($this->owner->can('update', $form))->toBeTrue()
        ->and($this->stranger->can('view', $form))->toBeFalse()
        ->and($this->stranger->can('update', $form))->toBeFalse();
});

it('lets the owning landlord manage application links but denies a stranger', function () {
    $link = ApplicationLink::factory()->create(['unit_id' => $this->unit->id]);

    expect($this->owner->can('create', [ApplicationLink::class, $this->unit]))->toBeTrue()
        ->and($this->owner->can('view', $link))->toBeTrue()
        ->and($this->owner->can('update', $link))->toBeTrue()
        ->and($this->owner->can('delete', $link))->toBeTrue()
        ->and($this->stranger->can('create', [ApplicationLink::class, $this->unit]))->toBeFalse()
        ->and($this->stranger->can('view', $link))->toBeFalse()
        ->and($this->stranger->can('update', $link))->toBeFalse()
        ->and($this->stranger->can('delete', $link))->toBeFalse();
});

it('lets the owning landlord view, update and delete an application but denies a stranger', function () {
    $link = ApplicationLink::factory()->create(['unit_id' => $this->unit->id]);
    $application = Application::factory()->create([
        'application_link_id' => $link->id,
        'unit_id' => $this->unit->id,
    ]);

    expect($this->owner->can('view', $application))->toBeTrue()
        ->and($this->owner->can('update', $application))->toBeTrue()
        ->and($this->owner->can('delete', $application))->toBeTrue()
        ->and($this->stranger->can('view', $application))->toBeFalse()
        ->and($this->stranger->can('update', $application))->toBeFalse()
        ->and($this->stranger->can('delete', $application))->toBeFalse();
});

it('lets the owning landlord view and download a document but denies a stranger', function () {
    $link = ApplicationLink::factory()->create(['unit_id' => $this->unit->id]);
    $application = Application::factory()->create([
        'application_link_id' => $link->id,
        'unit_id' => $this->unit->id,
    ]);
    $document = Document::factory()->create(['application_id' => $application->id]);

    expect($this->owner->can('view', $document))->toBeTrue()
        ->and($this->owner->can('download', $document))->toBeTrue()
        ->and($this->stranger->can('view', $document))->toBeFalse()
        ->and($this->stranger->can('download', $document))->toBeFalse();
});
