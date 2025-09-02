<?php

use App\Project\Livewire\ProjectCreateModal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('project can be created through modal', function () {
    $user = $this->createUser('User', 'user@example.com');
    $org = $this->createOrganization('Org', $user);

    $this->actingAs($user);

    Livewire::test(ProjectCreateModal::class)
        ->set('name', 'New Project')
        ->call('create')
        ->assertSet('name', '');

    expect($org->projects()->where('name', 'New Project')->exists())->toBeTrue();
});
