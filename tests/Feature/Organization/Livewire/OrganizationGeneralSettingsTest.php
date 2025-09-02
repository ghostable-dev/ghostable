<?php

use App\Organization\Livewire\OrganizationGeneralSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('organization name can be updated', function () {
    $user = $this->createUser('Peter', 'peter@example.com');
    $organization = $this->createOrganization('Ghostbusters', $user);

    $this->actingAs($user);

    Livewire::test(OrganizationGeneralSettings::class)
        ->set('name', 'Ghostbusters HQ')
        ->call('updateOrganizationName')
        ->assertDispatched('name-updated', name: 'Ghostbusters HQ');

    expect($organization->fresh()->name)->toBe('Ghostbusters HQ');
});
