<?php

use App\Organization\Enums\OrganizationAuditWebhookStatus;
use App\Organization\Jobs\DeliverAuditWebhookActivity;
use App\Organization\Models\OrganizationAuditWebhook;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->owner = $this->createUser(name: 'Owner User', email: 'owner-webhooks@example.com');
    $this->member = $this->createUser(name: 'Member User', email: 'member-webhooks@example.com');
    $this->organization = $this->createOrganization(
        name: 'Ghostable Audit Webhooks Org',
        owner: $this->owner,
        members: [$this->member]
    );
    $this->baseEndpoint = "/api/v2/organizations/{$this->organization->id}/audit-webhooks";
});

test('organization admin can create and list audit webhooks', function () {
    Sanctum::actingAs($this->owner);

    $create = $this->postJson($this->baseEndpoint, [
        'name' => 'Security SIEM',
        'endpoint_url' => 'https://siem.example.com/ghostable',
    ]);

    $create
        ->assertCreated()
        ->assertJsonPath('data.name', 'Security SIEM')
        ->assertJsonPath('data.status', OrganizationAuditWebhookStatus::ACTIVE->value);

    expect($create->json('meta.signing_secret'))->toBeString()->not->toBe('');

    $list = $this->getJson($this->baseEndpoint);

    $list
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Security SIEM')
        ->assertJsonPath('data.0.endpoint_url', 'https://siem.example.com/ghostable');
});

test('non-admin organization member cannot manage audit webhooks', function () {
    Sanctum::actingAs($this->member);

    $this->postJson($this->baseEndpoint, [
        'name' => 'Denied',
        'endpoint_url' => 'https://siem.example.com/ghostable',
    ])->assertForbidden();

    $this->getJson($this->baseEndpoint)->assertForbidden();
});

test('admin can disable and rotate audit webhook secret', function () {
    Sanctum::actingAs($this->owner);

    $create = $this->postJson($this->baseEndpoint, [
        'name' => 'Security SIEM',
        'endpoint_url' => 'https://siem.example.com/ghostable',
    ])->assertCreated();

    $webhookId = $create->json('data.id');
    $initialSecret = $create->json('meta.signing_secret');

    $this->postJson("{$this->baseEndpoint}/{$webhookId}/disable")
        ->assertOk()
        ->assertJsonPath('data.status', OrganizationAuditWebhookStatus::DISABLED->value);

    $rotate = $this->postJson("{$this->baseEndpoint}/{$webhookId}/rotate-secret")
        ->assertOk()
        ->assertJsonPath('data.status', OrganizationAuditWebhookStatus::ACTIVE->value);

    expect($rotate->json('meta.signing_secret'))->toBeString()->not->toBe($initialSecret);
});

test('admin webhook test endpoint sends signed payload', function () {
    Sanctum::actingAs($this->owner);

    Http::fake([
        'https://siem.example.com/ghostable' => Http::response(['ok' => true], 202),
    ]);

    $create = $this->postJson($this->baseEndpoint, [
        'name' => 'Security SIEM',
        'endpoint_url' => 'https://siem.example.com/ghostable',
    ])->assertCreated();

    $webhookId = $create->json('data.id');
    $secret = $create->json('meta.signing_secret');

    $this->postJson("{$this->baseEndpoint}/{$webhookId}/test")
        ->assertOk()
        ->assertJsonPath('data.status', OrganizationAuditWebhookStatus::ACTIVE->value);

    Http::assertSent(function (\Illuminate\Http\Client\Request $request) use ($secret): bool {
        $timestamp = $request->header('X-Ghostable-Timestamp')[0] ?? null;
        $signature = $request->header('X-Ghostable-Signature')[0] ?? null;
        $event = $request->header('X-Ghostable-Event')[0] ?? null;

        if (! $timestamp || ! $signature) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$request->body(), $secret);

        return $request->url() === 'https://siem.example.com/ghostable'
            && $event === 'webhook.test'
            && hash_equals("sha256={$expected}", $signature);
    });
});

test('creating an activity dispatches audit webhook delivery job for active endpoints', function () {
    Sanctum::actingAs($this->owner);

    Queue::fake();

    $webhook = OrganizationAuditWebhook::query()->create([
        'organization_id' => (string) $this->organization->id,
        'name' => 'Queue Target',
        'endpoint_url' => 'https://siem.example.com/ghostable',
        'signing_secret' => 'secret-key',
        'status' => OrganizationAuditWebhookStatus::ACTIVE,
        'created_by' => (string) $this->owner->id,
        'updated_by' => (string) $this->owner->id,
    ]);

    activity('organization')
        ->performedOn($this->organization)
        ->causedBy($this->owner)
        ->event('audit_webhook_test_event')
        ->withProperties([
            'organization_id' => (string) $this->organization->id,
        ])
        ->log('Queued audit webhook dispatch test');

    Queue::assertPushed(
        DeliverAuditWebhookActivity::class,
        fn (DeliverAuditWebhookActivity $job): bool => $job->webhookId === (string) $webhook->id
    );
});
