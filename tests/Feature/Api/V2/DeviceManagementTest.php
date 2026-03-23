<?php

use App\Crypto\Models\Device;
use App\Environment\Enums\EnvironmentType;
use App\Environment\Models\EnvironmentKeyReshareRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = $this->createUser('Ray', 'ray@ghostbusters.com');
    $this->endpoint = '/api/v2/devices';
});

test('unauthenticated users cannot register devices', function () {
    $this->postJson($this->endpoint, [
        'public_key' => base64_encode(random_bytes(32)),
        'public_signing_key' => base64_encode(random_bytes(32)),
        'platform' => 'ios',
    ])->assertUnauthorized();
});

test('can register a device', function () {
    Sanctum::actingAs($this->user);

    $payload = [
        'public_key' => base64_encode(random_bytes(32)),
        'public_signing_key' => base64_encode(random_bytes(32)),
        'platform' => 'macos',
    ];

    $response = $this->postJson($this->endpoint, $payload);

    $response->assertCreated()
        ->assertJsonPath('data.type', 'devices')
        ->assertJsonPath('data.attributes.public_key', $payload['public_key'])
        ->assertJsonPath('data.attributes.platform', $payload['platform']);

    $this->assertDatabaseHas('devices', [
        'public_key' => $payload['public_key'],
        'public_signing_key' => $payload['public_signing_key'],
        'user_id' => $this->user->id,
    ]);

    $device = Device::query()->where('public_key', $payload['public_key'])->firstOrFail();

    $activity = Activity::query()
        ->where('log_name', 'device')
        ->where('event', 'created')
        ->where('subject_id', $device->id)
        ->latest()
        ->first();

    expect($activity)->not->toBeNull();
    expect(data_get($activity->properties, 'device.platform'))->toBe('macos');
    expect(data_get($activity->properties, 'requested_by.email'))->toBe($this->user->email);
    expect(data_get($activity->properties, 'source'))->toBe('cli');
    expect(data_get($activity->properties, 'ip_address'))->toBe('127.0.0.1');
});

test('register device tolerates legacy cli platform payload', function () {
    Sanctum::actingAs($this->user);

    $payload = [
        'public_key' => base64_encode(random_bytes(32)),
        'public_signing_key' => base64_encode(random_bytes(32)),
        'platform' => 'cli',
    ];

    $response = $this->postJson($this->endpoint, $payload);

    $response->assertCreated()
        ->assertJsonPath('data.attributes.platform', 'unknown');

    $this->assertDatabaseHas('devices', [
        'public_key' => $payload['public_key'],
        'platform' => 'unknown',
        'client_type' => 'cli',
    ]);
});

test('register device defaults invalid platform to unknown', function () {
    Sanctum::actingAs($this->user);

    $payload = [
        'public_key' => base64_encode(random_bytes(32)),
        'public_signing_key' => base64_encode(random_bytes(32)),
        'platform' => 'totally-invalid-platform',
    ];

    $response = $this->postJson($this->endpoint, $payload);

    $response->assertCreated()
        ->assertJsonPath('data.attributes.platform', 'unknown');

    $this->assertDatabaseHas('devices', [
        'public_key' => $payload['public_key'],
        'platform' => 'unknown',
        'client_type' => 'cli',
    ]);
});

test('registering a new device creates pending key re-share requests when guided flow is enabled', function () {
    Sanctum::actingAs($this->user);

    $organization = $this->createOrganization('Ghostbusters', $this->user);
    $organization->features = $organization->features->withOverrides([
        'guided_key_reshare_v2' => true,
    ]);
    $organization->save();

    $project = $this->createProject('Containment Unit', $organization);
    $environment = $this->createEnvironment('production', EnvironmentType::PRODUCTION, $project);

    $existingDevice = $this->createDevice($this->user, 'Existing device');
    $environmentKey = $this->createEnvironmentKeyWithEnvelope(
        environment: $environment,
        createdByDevice: $existingDevice,
        recipients: [
            [
                'id' => (string) $existingDevice->id,
                'type' => 'device',
                'label' => 'Existing device',
            ],
        ],
    );

    $payload = [
        'public_key' => base64_encode(random_bytes(32)),
        'public_signing_key' => base64_encode(random_bytes(32)),
        'platform' => 'macos',
    ];

    $response = $this->postJson($this->endpoint, $payload)->assertCreated();

    $newDeviceId = (string) $response->json('data.id');

    $this->assertDatabaseHas('environment_key_reshare_requests', [
        'organization_id' => (string) $organization->id,
        'project_id' => (string) $project->id,
        'environment_id' => (string) $environment->id,
        'target_user_id' => (string) $this->user->id,
        'target_device_id' => $newDeviceId,
        'required_key_version' => $environmentKey->version,
        'status' => 'pending',
    ]);

    expect(EnvironmentKeyReshareRequest::query()->count())->toBe(1);
});

test('public key must be base64 and unique', function () {
    Sanctum::actingAs($this->user);

    $existingKey = base64_encode(random_bytes(32));
    Device::factory()->for($this->user)->create([
        'public_key' => $existingKey,
        'platform' => 'ios',
    ]);

    $response = $this->postJson($this->endpoint, [
        'public_key' => 'not-base64',
        'public_signing_key' => base64_encode(random_bytes(32)),
        'platform' => 'ios',
    ]);

    $response->assertStatus(422);
    expect($response->json('error.fields.public_key'))->toBeArray();

    $signingKeyResponse = $this->postJson($this->endpoint, [
        'public_key' => base64_encode(random_bytes(32)),
        'public_signing_key' => 'not-base64',
        'platform' => 'ios',
    ]);

    $signingKeyResponse->assertStatus(422);
    expect($signingKeyResponse->json('error.fields.public_signing_key'))->toBeArray();

    $uniqueResponse = $this->postJson($this->endpoint, [
        'public_key' => $existingKey,
        'public_signing_key' => base64_encode(random_bytes(32)),
        'platform' => 'ios',
    ]);

    $uniqueResponse->assertStatus(422);
    expect($uniqueResponse->json('error.fields.public_key'))->toBeArray();
});

test('can fetch device info', function () {
    Sanctum::actingAs($this->user);

    $device = Device::factory()->for($this->user)->create([
        'public_key' => base64_encode(random_bytes(32)),
        'platform' => 'linux',
        'last_seen_at' => now()->subMinutes(5),
    ]);

    $response = $this->getJson("{$this->endpoint}/{$device->id}");

    $response->assertOk()
        ->assertJsonPath('data.id', (string) $device->id)
        ->assertJsonPath('data.attributes.status', 'active')
        ->assertJsonPath('data.attributes.public_key', $device->public_key);
});

test('can fetch device info when legacy platform values are stored in DB', function () {
    Sanctum::actingAs($this->user);

    $device = Device::factory()->for($this->user)->create([
        'platform' => 'darwin-arm64 (23.6.0)',
        'public_key' => base64_encode(random_bytes(32)),
    ]);

    $response = $this->getJson("{$this->endpoint}/{$device->id}");

    $response->assertOk()
        ->assertJsonPath('data.attributes.platform', 'macos');
});

test('cannot fetch device for another user', function () {
    Sanctum::actingAs($this->user);

    $peter = $this->createUser('Peter', 'peter@ghostbusters.com');
    $device = Device::factory()->for($peter)->create([
        'public_key' => base64_encode(random_bytes(32)),
        'platform' => 'unknown',
    ]);

    $this->getJson("{$this->endpoint}/{$device->id}")->assertForbidden();
});

test('can revoke a device', function () {
    Sanctum::actingAs($this->user);

    $device = Device::factory()->for($this->user)->create([
        'public_key' => base64_encode(random_bytes(32)),
        'platform' => 'windows',
    ]);

    $response = $this->deleteJson("{$this->endpoint}/{$device->id}");

    $response->assertOk()
        ->assertJsonPath('meta.success', true)
        ->assertJsonPath('data.attributes.status', 'revoked');

    $this->assertDatabaseHas('devices', [
        'id' => $device->id,
        'active' => false,
    ]);

    $activity = Activity::query()
        ->where('log_name', 'device')
        ->where('event', 'revoked')
        ->where('subject_id', $device->id)
        ->latest()
        ->first();

    expect($activity)->not->toBeNull();
    expect(data_get($activity->properties, 'device.status'))->toBe('revoked');
    expect(data_get($activity->properties, 'requested_by.email'))->toBe($this->user->email);
    expect(data_get($activity->properties, 'ip_address'))->toBe('127.0.0.1');
});

test('revoking a device cancels pending key re-share requests for that device', function () {
    Sanctum::actingAs($this->user);

    $organization = $this->createOrganization('Ghostbusters', $this->user);
    $organization->features = $organization->features->withOverrides([
        'guided_key_reshare_v2' => true,
    ]);
    $organization->save();

    $project = $this->createProject('Containment Unit', $organization);
    $environment = $this->createEnvironment('production', EnvironmentType::PRODUCTION, $project);
    $device = $this->createDevice($this->user, 'Revoked target');

    EnvironmentKeyReshareRequest::query()->create([
        'organization_id' => $organization->id,
        'project_id' => $project->id,
        'environment_id' => $environment->id,
        'required_key_version' => 1,
        'target_user_id' => $this->user->id,
        'target_device_id' => $device->id,
        'status' => 'pending',
        'trigger_source' => 'device_link',
    ]);

    $this->deleteJson("{$this->endpoint}/{$device->id}")->assertOk();

    $this->assertDatabaseHas('environment_key_reshare_requests', [
        'target_device_id' => (string) $device->id,
        'status' => 'cancelled',
        'cancel_reason' => 'device_revoked',
    ]);
});

test('cannot revoke device for another user', function () {
    Sanctum::actingAs($this->user);

    $peter = $this->createUser('Peter', 'peter@ghostbusters.com');
    $device = Device::factory()->for($peter)->create([
        'public_key' => base64_encode(random_bytes(32)),
        'platform' => 'unknown',
    ]);

    $this->deleteJson("{$this->endpoint}/{$device->id}")->assertForbidden();
});
