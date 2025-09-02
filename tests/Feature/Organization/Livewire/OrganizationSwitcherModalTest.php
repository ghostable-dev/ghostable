<?php

use App\Organization\Livewire\OrganizationSwitcherModal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('organization switcher modal can switch organizations', function () {
    $user = $this->createUser('Owner', 'owner@example.com');
    $org1 = $this->createOrganization('Ghostbusters', $user);
    $org2 = $this->createOrganization('Spengler Labs', $user);

    session()->put('show-organization-switcher', true);
    $this->actingAs($user);

    Livewire::test(OrganizationSwitcherModal::class)
        ->assertSet('showing', true)
        ->call('switchToOrganization', $org2->id)
        ->assertRedirect(route('dashboard', absolute: false));

    expect(session('current_organization_id'))->toBe($org2->id);
});
