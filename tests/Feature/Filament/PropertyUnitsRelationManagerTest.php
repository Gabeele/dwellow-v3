<?php

use App\Filament\Resources\Properties\Pages\ViewProperty;
use App\Filament\Resources\Properties\RelationManagers\UnitsRelationManager;
use App\Models\Property;
use App\Models\Unit;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->actingAs(User::factory()->admin()->create());
});

it("lists a property's units for an admin", function () {
    $property = Property::factory()->multiUnit()->create();
    $unit = Unit::factory()->for($property)->create(['label' => 'Apartment 2B']);
    $otherUnit = Unit::factory()->create(['label' => 'Some Other Unit']);

    Livewire::test(UnitsRelationManager::class, [
        'ownerRecord' => $property,
        'pageClass' => ViewProperty::class,
    ])
        ->assertOk()
        ->assertCanSeeTableRecords([$unit])
        ->assertCanNotSeeTableRecords([$otherUnit])
        ->assertSee('Apartment 2B');
});
