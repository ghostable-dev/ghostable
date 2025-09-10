<?php

use App\Organization\Livewire\OrganizationBillingSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('organization billing settings can render', function () {
    $user = $this->createUser('Peter', 'peter@example.com');
    $this->createOrganization('Ghostbusters', $user);

    $this->actingAs($user);

    Livewire::test(OrganizationBillingSettings::class)
        ->assertViewIs('organization.organization-billing-settings');
});
