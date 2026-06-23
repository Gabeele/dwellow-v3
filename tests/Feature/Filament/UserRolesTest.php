<?php

use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->actingAs(User::factory()->admin()->create());
});

it('lets an admin assign roles to a user', function () {
    $user = User::factory()->create();

    Livewire::test(EditUser::class, ['record' => $user->id])
        ->fillForm([
            'roles' => ['landlord', 'tenant'],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $user->refresh();

    expect($user->isLandlord())->toBeTrue()
        ->and($user->isTenant())->toBeTrue()
        ->and($user->roles)->toHaveCount(2);
});

it('lets an admin remove a role from a user', function () {
    $user = User::factory()->landlord()->tenant()->create();

    Livewire::test(EditUser::class, ['record' => $user->id])
        ->fillForm([
            'roles' => ['landlord'],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $user->refresh();

    expect($user->isLandlord())->toBeTrue()
        ->and($user->isTenant())->toBeFalse()
        ->and($user->roles)->toHaveCount(1);
});

it('pre-fills the form with the user\'s current roles', function () {
    $user = User::factory()->landlord()->create();

    Livewire::test(EditUser::class, ['record' => $user->id])
        ->assertFormSet([
            'roles' => ['landlord'],
        ]);
});
