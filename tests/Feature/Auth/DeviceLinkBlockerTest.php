<?php

use App\Auth\Livewire\DeviceLinkBlocker;
use App\Organization\Enums\OrganizationRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('device link blocker stays hidden when user has an active device', function () {
    $user = $this->createUser('Tester', 'tester@example.com');
    $this->createDevice($user, 'MacBook Pro');

    $this->actingAs($user);

    $component = Livewire::test(DeviceLinkBlocker::class)
        ->assertViewIs('livewire.auth.device-link-blocker')
        ->assertDontSee('Getting Started');

    expect($component->get('requiresDeviceLink'))->toBeFalse();
});

test('device link reminder banner shows when organization has an active device but user does not', function () {
    $owner = $this->createUser('Owner', 'owner@example.com');
    $org = $this->createOrganization('Org', $owner);
    $this->createDevice($owner, 'Owner Laptop');

    $member = $this->createUser('Member', 'member@example.com');
    $member->organizationMembership()->assignToOrganization(
        organization: $org,
        role: OrganizationRole::DEVELOPER
    );

    $this->actingAs($member);

    $component = Livewire::test(DeviceLinkBlocker::class)
        ->assertViewIs('livewire.auth.device-link-blocker')
        ->assertSee('Device link needed')
        ->assertSee('Set up this device');

    expect($component->get('requiresDeviceLink'))->toBeFalse()
        ->and($component->get('showDeviceReminderBanner'))->toBeTrue();
});

test('device link blocker shows when neither user nor organization has an active device', function () {
    $user = $this->createUser('NoDevice', 'nodevice@example.com');
    $org = $this->createOrganization('Org', $user);

    $this->actingAs($user);

    $component = Livewire::test(DeviceLinkBlocker::class)
        ->assertViewIs('livewire.auth.device-link-blocker')
        ->assertSee('Getting Started')
        ->assertSee('Download Desktop for macOS')
        ->assertSee('Desktop')
        ->assertSee('CLI');

    expect($component->get('requiresDeviceLink'))->toBeTrue();
});

test('device link blocker stays hidden for billing-only roles when organization already has an active device', function () {
    $owner = $this->createUser('Owner', 'owner@example.com');
    $org = $this->createOrganization('Org', $owner);
    $this->createDevice($owner, 'Owner Laptop');

    $member = $this->createUser('Member', 'member@example.com');
    $member->organizationMembership()->assignToOrganization(
        organization: $org,
        role: OrganizationRole::BILLING_ONLY
    );

    $this->actingAs($member);

    $component = Livewire::test(DeviceLinkBlocker::class)
        ->assertViewIs('livewire.auth.device-link-blocker')
        ->assertDontSee('Getting Started');

    expect($component->get('requiresDeviceLink'))->toBeFalse()
        ->and($component->get('showDeviceReminderBanner'))->toBeFalse();
});
