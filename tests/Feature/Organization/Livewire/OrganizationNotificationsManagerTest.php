<?php

use App\Organization\Enums\OrganizationNotification;
use App\Organization\Livewire\OrganizationNotificationsManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('organization notification can be toggled', function () {
    $user = $this->createUser('Owner', 'owner@example.com');
    $organization = $this->createOrganization('Ghostbusters', $user);

    $this->actingAs($user);

    Livewire::test(OrganizationNotificationsManager::class)
        ->call('toggle', OrganizationNotification::MEMBERSHIP_ACTIVITY->value);

    expect($organization->fresh()->notifications->membership_activity)->toBeFalse();
});

test('slack settings can be saved', function () {
    $user = $this->createUser('Owner', 'owner@example.com');
    $organization = $this->createOrganization('Ghostbusters', $user);

    $this->actingAs($user);

    Livewire::test(OrganizationNotificationsManager::class)
        ->set('slack_webhook_url', 'https://hooks.slack.com/services/test')
        ->call('saveSlackSettings')
        ->assertDispatched('slack-webhook-updated');

    expect($organization->fresh()->slack_webhook_url)
        ->toBe('https://hooks.slack.com/services/test');
});
