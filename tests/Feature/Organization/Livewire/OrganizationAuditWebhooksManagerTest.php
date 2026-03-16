<?php

use App\Organization\Enums\OrganizationAuditWebhookStatus;
use App\Organization\Enums\OrganizationRole;
use App\Organization\Livewire\OrganizationAuditWebhooksManager;
use App\Organization\Models\OrganizationAuditWebhook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('organization admin can create audit webhook and receives signing secret', function () {
    $owner = $this->createUser('Owner', 'owner@example.com');
    $organization = $this->createOrganization('Ghostbusters', $owner);

    $this->actingAs($owner);

    $component = Livewire::test(OrganizationAuditWebhooksManager::class)
        ->set('name', 'Security SIEM')
        ->set('endpointUrl', 'https://siem.example.com/ghostable')
        ->call('createWebhook')
        ->assertHasNoErrors();

    $webhook = OrganizationAuditWebhook::query()->first();

    expect($webhook)->not->toBeNull();
    expect((string) $webhook->organization_id)->toBe((string) $organization->id);
    expect($webhook->name)->toBe('Security SIEM');
    expect($webhook->endpoint_url)->toBe('https://siem.example.com/ghostable');
    expect($webhook->status)->toBe(OrganizationAuditWebhookStatus::ACTIVE);

    $secret = $component->get('lastSigningSecret');
    expect($secret)->toBeString()->not->toBe('');
});

test('non-admin organization member cannot create audit webhook', function () {
    $owner = $this->createUser('Owner', 'owner@example.com');
    $organization = $this->createOrganization('Ghostbusters', $owner);
    $developer = $this->createUser('Developer', 'developer@example.com');
    $developer->organizationMembership()->assignToOrganization($organization, OrganizationRole::DEVELOPER);

    $this->actingAs($developer);

    Livewire::test(OrganizationAuditWebhooksManager::class)
        ->set('name', 'Denied')
        ->set('endpointUrl', 'https://siem.example.com/ghostable')
        ->call('createWebhook')
        ->assertForbidden();
});

test('organization admin can test disable and rotate audit webhook', function () {
    $owner = $this->createUser('Owner', 'owner@example.com');
    $organization = $this->createOrganization('Ghostbusters', $owner);

    $webhook = OrganizationAuditWebhook::query()->create([
        'organization_id' => (string) $organization->id,
        'name' => 'Security SIEM',
        'endpoint_url' => 'https://siem.example.com/ghostable',
        'signing_secret' => 'original-secret',
        'status' => OrganizationAuditWebhookStatus::ACTIVE,
        'created_by' => (string) $owner->id,
        'updated_by' => (string) $owner->id,
    ]);

    Http::fake([
        'https://siem.example.com/ghostable' => Http::response(['ok' => true], 202),
    ]);

    $this->actingAs($owner);

    Livewire::test(OrganizationAuditWebhooksManager::class)
        ->call('testWebhook', (string) $webhook->id)
        ->call('disableWebhook', (string) $webhook->id)
        ->call('rotateWebhookSecret', (string) $webhook->id);

    $webhook->refresh();

    expect($webhook->last_delivered_at)->not->toBeNull();
    expect($webhook->status)->toBe(OrganizationAuditWebhookStatus::ACTIVE);
    expect($webhook->consecutive_failures)->toBe(0);
    expect($webhook->disabled_at)->toBeNull();
    expect($webhook->signing_secret)->not->toBe('original-secret');
});

test('organization admin can apply local receiver presets', function () {
    config()->set('audit_webhook_receiver.local_routes_enabled', true);
    config()->set('audit_webhook_receiver.token', 'local-dev-token');

    $owner = $this->createUser('Owner', 'owner-local-helper@example.com');
    $this->createOrganization('Ghostbusters', $owner);

    $this->actingAs($owner);

    $expected = url('/local/audit-webhooks/ingest').'?mode=slow&delay_ms=1500&token=local-dev-token';

    Livewire::test(OrganizationAuditWebhooksManager::class)
        ->call('useLocalReceiver', 'slow')
        ->assertSet('endpointUrl', $expected);
});

test('local receiver presets are hidden for screenshot renders', function () {
    config()->set('audit_webhook_receiver.local_routes_enabled', true);

    $owner = $this->createUser('Owner', 'owner-screenshot@example.com');
    $this->createOrganization('Ghostbusters', $owner);

    $this->actingAs($owner);

    Livewire::withQueryParams(['screenshot' => '1'])
        ->test(OrganizationAuditWebhooksManager::class)
        ->assertSet('localAuditReceiverEnabled', false);
});
