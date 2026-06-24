<?php

use App\Models\ApplicationLink;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('auto-generates an unguessable token on creation', function () {
    $link = ApplicationLink::factory()->create();

    expect($link->token)->toBeString()->toHaveLength(40);
});

it('keeps an explicitly provided token', function () {
    $link = ApplicationLink::factory()->create(['token' => 'my-custom-token']);

    expect($link->token)->toBe('my-custom-token');
});

it('generates a unique token per link', function () {
    $tokens = ApplicationLink::factory()->count(5)->create()->pluck('token');

    expect($tokens->unique())->toHaveCount(5);
});

it('belongs to a unit', function () {
    $unit = Unit::factory()->create();
    $link = ApplicationLink::factory()->create(['unit_id' => $unit->id]);

    expect($link->unit->is($unit))->toBeTrue();
});

it('is open when accepting, not revoked and not expired', function () {
    $link = ApplicationLink::factory()->create();

    expect($link->isOpen())->toBeTrue();
});

it('stays open when expiry is in the future', function () {
    $link = ApplicationLink::factory()->create(['expires_at' => now()->addDay()]);

    expect($link->isOpen())->toBeTrue();
});

it('is not open when revoked', function () {
    $link = ApplicationLink::factory()->revoked()->create();

    expect($link->isOpen())->toBeFalse();
});

it('is not open when expired', function () {
    $link = ApplicationLink::factory()->expired()->create();

    expect($link->isOpen())->toBeFalse();
});

it('is not open when not accepting', function () {
    $link = ApplicationLink::factory()->notAccepting()->create();

    expect($link->isOpen())->toBeFalse();
});
