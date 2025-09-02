<?php

use App\Organization\Livewire\OrganizationNotificationsSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('organization notifications settings can render', function () {
    $user = $this->createUser('Peter', 'peter@example.com');
    $this->createOrganization('Ghostbusters', $user);

    $this->actingAs($user);

    Livewire::test(OrganizationNotificationsSettings::class)
        ->assertViewIs('organization.organization-notifications-settings');
});
