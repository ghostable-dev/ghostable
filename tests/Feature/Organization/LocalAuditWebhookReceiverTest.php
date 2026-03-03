<?php

use App\Core\Models\Activity;
use App\Organization\Models\LocalAuditWebhookCapture;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('audit_webhook_receiver.local_routes_enabled', true);
    config()->set('audit_webhook_receiver.driver', 'null');
    config()->set('audit_webhook_receiver.token', null);
});

test('local receiver returns not found when disabled', function () {
    config()->set('audit_webhook_receiver.local_routes_enabled', false);

    $this->postJson('/local/audit-webhooks/ingest', [])
        ->assertNotFound();
});

test('null driver accepts requests without persisting captures', function () {
    $this->postJson('/local/audit-webhooks/ingest?mode=ok', [
        'id' => 'evt-null-1',
        'event' => 'webhook.test',
        'organization_id' => 'org-local-null',
    ])->assertStatus(202)
        ->assertJsonPath('mode', 'ok')
        ->assertJsonPath('driver', 'null');

    expect(Schema::hasTable('local_audit_webhook_captures'))->toBeFalse();
});

test('log driver writes structured capture context', function () {
    config()->set('audit_webhook_receiver.driver', 'log');
    config()->set('audit_webhook_receiver.log_channel', 'audit_webhook_receiver');

    Log::shouldReceive('channel')->once()->with('audit_webhook_receiver')->andReturnSelf();
    Log::shouldReceive('info')->once()->withArgs(function ($message, $context): bool {
        if ($message !== 'local_audit_webhook_capture' || ! is_array($context)) {
            return false;
        }

        return ($context['event_id'] ?? null) === 'evt-log-1'
            && ($context['event_type'] ?? null) === 'webhook.test'
            && ($context['organization_id'] ?? null) === 'org-local-log'
            && ($context['response_status'] ?? null) === 202;
    });

    $this->postJson('/local/audit-webhooks/ingest?mode=ok', [
        'id' => 'evt-log-1',
        'event' => 'webhook.test',
        'organization_id' => 'org-local-log',
    ])->assertStatus(202);
});

test('database driver stores captures and supports mode simulation', function () {
    config()->set('audit_webhook_receiver.driver', 'database');
    $this->artisan('local:audit-webhooks:install-captures-table')->assertSuccessful();

    $this->postJson('/local/audit-webhooks/ingest?mode=ok', [
        'id' => 'evt-db-1',
        'event' => 'webhook.test',
        'organization_id' => 'org-local-db',
    ])->assertStatus(202);

    $this->postJson('/local/audit-webhooks/ingest?mode=fail', [
        'id' => 'evt-db-2',
        'event' => 'webhook.fail',
        'organization_id' => 'org-local-db',
    ])->assertStatus(500);

    $this->postJson('/local/audit-webhooks/ingest?mode=slow&delay_ms=10', [
        'id' => 'evt-db-3',
        'event' => 'webhook.slow',
        'organization_id' => 'org-local-db',
    ])->assertStatus(202);

    $this->assertDatabaseHas('local_audit_webhook_captures', [
        'event_id' => 'evt-db-1',
        'event_type' => 'webhook.test',
        'organization_id' => 'org-local-db',
        'mode' => 'ok',
        'response_status' => 202,
    ]);

    $this->assertDatabaseHas('local_audit_webhook_captures', [
        'event_id' => 'evt-db-2',
        'event_type' => 'webhook.fail',
        'organization_id' => 'org-local-db',
        'mode' => 'fail',
        'response_status' => 500,
    ]);

    $this->assertDatabaseHas('local_audit_webhook_captures', [
        'event_id' => 'evt-db-3',
        'event_type' => 'webhook.slow',
        'organization_id' => 'org-local-db',
        'mode' => 'slow',
        'response_status' => 202,
    ]);
});

test('database driver returns capture unavailable when table is missing', function () {
    config()->set('audit_webhook_receiver.driver', 'database');

    $this->postJson('/local/audit-webhooks/ingest?mode=ok', [
        'id' => 'evt-db-missing-1',
        'event' => 'webhook.test',
    ])->assertStatus(503)
        ->assertJsonPath('status', 'capture_unavailable');
});

test('receiver token is enforced when configured', function () {
    config()->set('audit_webhook_receiver.token', 'local-token');

    $this->postJson('/local/audit-webhooks/ingest?mode=ok', [])
        ->assertForbidden();

    $this->postJson('/local/audit-webhooks/ingest?mode=ok&token=wrong', [])
        ->assertForbidden();

    $this->postJson('/local/audit-webhooks/ingest?mode=ok&token=local-token', [
        'id' => 'evt-token-1',
    ])->assertStatus(202);
});

test('inbox and clear routes require auth and can clear database captures', function () {
    config()->set('audit_webhook_receiver.driver', 'database');
    $this->artisan('local:audit-webhooks:install-captures-table')->assertSuccessful();

    $this->postJson('/local/audit-webhooks/ingest?mode=ok', [
        'id' => 'evt-inbox-1',
        'event' => 'webhook.test',
    ])->assertStatus(202);

    $this->get('/local/audit-webhooks/inbox')
        ->assertRedirect('/login');

    $user = $this->createUser('Owner', 'owner-local-inbox@example.com');
    $this->actingAs($user);

    $this->get('/local/audit-webhooks/inbox')
        ->assertOk()
        ->assertSee('Local Audit Webhook Inbox')
        ->assertSee('evt-inbox-1');

    $this->delete('/local/audit-webhooks/inbox')
        ->assertRedirect(route('local.audit-webhooks.inbox'));

    expect(LocalAuditWebhookCapture::query()->count())->toBe(0);
});

test('receiver ingestion does not create activity records', function () {
    config()->set('audit_webhook_receiver.driver', 'database');
    $this->artisan('local:audit-webhooks:install-captures-table')->assertSuccessful();

    $before = Activity::query()->count();

    $this->postJson('/local/audit-webhooks/ingest?mode=ok', [
        'id' => 'evt-no-loop-1',
        'event' => 'webhook.test',
        'organization_id' => 'org-loop-check',
    ])->assertStatus(202);

    expect(Activity::query()->count())->toBe($before);
});

test('capture table install command skips for non-database driver', function () {
    config()->set('audit_webhook_receiver.driver', 'null');

    $this->artisan('local:audit-webhooks:install-captures-table')
        ->expectsOutputToContain('Skipped.')
        ->assertSuccessful();

    expect(Schema::hasTable('local_audit_webhook_captures'))->toBeFalse();
});

test('capture table install command creates table for database driver', function () {
    config()->set('audit_webhook_receiver.driver', 'database');

    $this->artisan('local:audit-webhooks:install-captures-table')
        ->expectsOutputToContain('Created local_audit_webhook_captures table.')
        ->assertSuccessful();

    expect(Schema::hasTable('local_audit_webhook_captures'))->toBeTrue();
});
