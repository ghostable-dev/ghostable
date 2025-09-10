<?php

use App\Account\Models\User;
use App\Organization\Models\Invite;
use App\Organization\Models\Organization;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('determines panel access based on founder email', function () {
    $founder = User::factory()->create(['email' => 'rucci.joe@gmail.com']);
    $regular = User::factory()->create(['email' => 'user@example.com']);

    $panel = Panel::make();

    expect($founder->canAccessPanel($panel))->toBeTrue();
    expect($regular->canAccessPanel($panel))->toBeFalse();
});

it('returns pending invites for the user', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    Invite::withoutEvents(fn () => Invite::create([
        'email' => $user->email,
        'organization_id' => $organization->id,
    ]));

    expect($user->pendingInvites())->toHaveCount(1);
});
