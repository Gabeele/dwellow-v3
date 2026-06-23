<?php

use App\Filament\Resources\Properties\Pages\ListProperties;
use App\Filament\Resources\Properties\Pages\ViewProperty;
use App\Models\Property;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->actingAs(User::factory()->admin()->create());
});

it('lists properties for an admin', function () {
    $property = Property::factory()->create(['name' => 'Maple Street Rental']);

    Livewire::test(ListProperties::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$property])
        ->assertSee('Maple Street Rental');
});

it('shows a property detail page for an admin', function () {
    $property = Property::factory()->create(['name' => 'Maple Street Rental']);

    Livewire::test(ViewProperty::class, ['record' => $property->id])
        ->assertOk()
        ->assertSee('Maple Street Rental');
});
