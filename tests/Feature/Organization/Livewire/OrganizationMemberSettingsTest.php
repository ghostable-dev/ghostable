<?php

use App\Organization\Livewire\OrganizationMemberSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('organization member settings can render', function () {
    $user = $this->createUser('Peter', 'peter@example.com');
    $this->createOrganization('Ghostbusters', $user);

    $this->actingAs($user);

    Livewire::test(OrganizationMemberSettings::class)
        ->assertViewIs('organization.organization-member-settings');
});
