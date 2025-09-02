<?php

use App\Organization\Livewire\OrganizationDropdown;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('user can switch organizations from dropdown', function () {
    $user = $this->createUser('Peter', 'peter@example.com');
    $org1 = $this->createOrganization('Ghostbusters', $user);
    $org2 = $this->createOrganization('Spengler Labs', $user);

    $this->actingAs($user);

    Livewire::test(OrganizationDropdown::class)
        ->call('switchToOrganization', $org2->id)
        ->assertRedirect(route('dashboard', absolute: false));

    expect(session('current_organization_id'))->toBe($org2->id);
});
