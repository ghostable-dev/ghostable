<?php

use App\Organization\Enums\OrganizationAuditWebhookStatus;
use App\Organization\Jobs\DeliverAuditWebhookActivity;
use App\Organization\Models\OrganizationAuditWebhook;
use App\Organization\Models\OrganizationAuditWebhookDelivery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

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
    $this->getJson("{$this->baseEndpoint}/metrics")->assertForbidden();
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

    Http::assertSent(function (Illuminate\Http\Client\Request $request) use ($secret): bool {
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

test('admin webhook test endpoint can flow into local receiver capture storage', function () {
    Sanctum::actingAs($this->owner);
    config()->set('audit_webhook_receiver.local_routes_enabled', true);
    config()->set('audit_webhook_receiver.driver', 'database');
    $this->artisan('local:audit-webhooks:install-captures-table')->assertSuccessful();

    $localEndpoint = url('/local/audit-webhooks/ingest?mode=ok');

    Http::fake(function (Illuminate\Http\Client\Request $request) use ($localEndpoint) {
        if ($request->url() !== $localEndpoint) {
            return Http::response(['ok' => true], 202);
        }

        $headers = [
            'X-Ghostable-Timestamp' => $request->header('X-Ghostable-Timestamp')[0] ?? '',
            'X-Ghostable-Signature' => $request->header('X-Ghostable-Signature')[0] ?? '',
            'X-Ghostable-Event' => $request->header('X-Ghostable-Event')[0] ?? '',
        ];

        $response = $this->postJson(
            '/local/audit-webhooks/ingest?mode=ok',
            json_decode($request->body(), true) ?? [],
            $headers
        );

        return Http::response($response->json(), $response->status());
    });

    $create = $this->postJson($this->baseEndpoint, [
        'name' => 'Local Capture Target',
        'endpoint_url' => $localEndpoint,
    ])->assertCreated();

    $webhookId = (string) $create->json('data.id');

    $this->postJson("{$this->baseEndpoint}/{$webhookId}/test")
        ->assertOk();

    $this->assertDatabaseHas('organization_audit_webhook_deliveries', [
        'organization_audit_webhook_id' => $webhookId,
        'status' => 'delivered',
    ]);

    $this->assertDatabaseHas('local_audit_webhook_captures', [
        'event_type' => 'webhook.test',
        'mode' => 'ok',
        'response_status' => 202,
    ]);
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

test('creating an activity during screenshot requests does not dispatch audit webhook delivery jobs', function () {
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

    app()->instance('request', Request::create('/', 'GET', server: [
        'HTTP_X_GHOSTABLE_SCREENSHOT' => '1',
    ]));

    activity('organization')
        ->performedOn($this->organization)
        ->causedBy($this->owner)
        ->event('audit_webhook_test_event')
        ->withProperties([
            'organization_id' => (string) $this->organization->id,
        ])
        ->log('Skipped audit webhook dispatch for screenshot request');

    Queue::assertNotPushed(
        DeliverAuditWebhookActivity::class,
        fn (DeliverAuditWebhookActivity $job): bool => $job->webhookId === (string) $webhook->id
    );
});

test('organization admin can fetch webhook delivery metrics', function () {
    Sanctum::actingAs($this->owner);

    $webhook = OrganizationAuditWebhook::query()->create([
        'organization_id' => (string) $this->organization->id,
        'name' => 'Metrics Target',
        'endpoint_url' => 'https://siem.example.com/ghostable',
        'signing_secret' => 'secret-key',
        'status' => OrganizationAuditWebhookStatus::ACTIVE,
        'created_by' => (string) $this->owner->id,
        'updated_by' => (string) $this->owner->id,
    ]);

    OrganizationAuditWebhookDelivery::query()->create([
        'organization_audit_webhook_id' => (string) $webhook->id,
        'organization_id' => (string) $this->organization->id,
        'event_id' => 'evt-1',
        'event_type' => 'environment.push',
        'status' => 'delivered',
        'http_status' => 200,
        'latency_ms' => 92,
        'attempt_number' => 1,
        'delivered_at' => now()->subMinutes(20),
        'created_at' => now()->subMinutes(20),
        'updated_at' => now()->subMinutes(20),
    ]);

    OrganizationAuditWebhookDelivery::query()->create([
        'organization_audit_webhook_id' => (string) $webhook->id,
        'organization_id' => (string) $this->organization->id,
        'event_id' => 'evt-2',
        'event_type' => 'environment.push',
        'status' => 'failed',
        'http_status' => 500,
        'latency_ms' => 240,
        'attempt_number' => 1,
        'error_message' => 'HTTP 500',
        'created_at' => now()->subMinutes(10),
        'updated_at' => now()->subMinutes(10),
    ]);

    $response = $this->getJson("{$this->baseEndpoint}/metrics?window=24h");

    $response
        ->assertOk()
        ->assertJsonPath('data.window', '24h')
        ->assertJsonPath('data.summary.attempted', 2)
        ->assertJsonPath('data.summary.succeeded', 1)
        ->assertJsonPath('data.summary.failed', 1)
        ->assertJsonPath('data.webhooks.0.id', (string) $webhook->id)
        ->assertJsonPath('data.webhooks.0.attempted', 2)
        ->assertJsonPath('data.webhooks.0.succeeded', 1)
        ->assertJsonPath('data.webhooks.0.failed', 1);
});
