<?php

use App\Organization\Livewire\OrganizationCreateModal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('organization can be created from modal', function () {
    $user = $this->createUser('Peter', 'peter@example.com');

    $this->actingAs($user);

    Livewire::test(OrganizationCreateModal::class)
        ->set('name', 'Spengler Labs')
        ->call('create')
        ->assertRedirect(route('dashboard', absolute: false));

    expect($user->fresh()->organizations()->where('name', 'Spengler Labs')->exists())->toBeTrue();
});
