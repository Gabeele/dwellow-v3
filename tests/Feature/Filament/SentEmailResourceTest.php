<?php

use App\Filament\Resources\SentEmails\Pages\ListSentEmails;
use App\Filament\Resources\SentEmails\Pages\ViewSentEmail;
use App\Models\SentEmail;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->actingAs(User::factory()->admin()->create());
});

it('lists sent emails for an admin', function () {
    $email = SentEmail::factory()->create(['subject' => 'Welcome aboard']);

    Livewire::test(ListSentEmails::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$email])
        ->assertSee('Welcome aboard');
});

it('shows a sent email detail page for an admin', function () {
    $email = SentEmail::factory()->create([
        'subject' => 'Welcome aboard',
        'body' => '<p>Thanks for joining dwellow.</p>',
    ]);

    Livewire::test(ViewSentEmail::class, ['record' => $email->id])
        ->assertOk()
        ->assertSee('Welcome aboard');
});
