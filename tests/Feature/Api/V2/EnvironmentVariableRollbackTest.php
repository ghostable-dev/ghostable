<?php

declare(strict_types=1);

use App\Crypto\Models\Device;
use App\Environment\Enums\EnvironmentType;
use App\Environment\Models\EnvironmentSecret;
use App\Environment\Models\EnvironmentSecretVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = $this->createUser('Dana', 'dana@example.com');
    $this->organization = $this->createOrganization('Ghostbusters', $this->user);
    $this->project = $this->createProject('Containment Unit', $this->organization);
    $this->environment = $this->createEnvironment('production', EnvironmentType::PRODUCTION, $this->project);

    $this->variableName = 'DB_PASSWORD';
    $this->endpoint = sprintf(
        '/api/v2/projects/%s/environments/%s/variables/%s/rollback',
        $this->project->id,
        $this->environment->name,
        $this->variableName
    );

    $this->deviceKeypair = sodium_crypto_sign_keypair();
    $this->deviceSecretKey = sodium_crypto_sign_secretkey($this->deviceKeypair);
    $this->devicePublicKey = sodium_crypto_sign_publickey($this->deviceKeypair);

    $this->device = Device::factory()->for($this->user)->create([
        'platform' => 'macos',
        'client_type' => 'cli',
        'public_key' => base64_encode(random_bytes(32)),
        'public_signing_key' => base64_encode($this->devicePublicKey),
    ]);

    $this->secret = EnvironmentSecret::query()->create([
        'environment_id' => $this->environment->id,
        'name' => $this->variableName,
        'ciphertext' => base64_encode('current-secret'),
        'nonce' => base64_encode(random_bytes(24)),
        'alg' => 'xchacha20-poly1305',
        'aad' => ['env' => (string) $this->environment->id],
        'claims' => ['hmac' => 'current-hmac'],
        'client_sig' => base64_encode(random_bytes(64)),
        'env_kek_version' => 3,
        'env_kek_fingerprint' => 'fingerprint-current',
        'metadata' => ['laravel' => ['is_vapor_secret' => false]],
        'line_bytes' => 32,
        'is_commented' => false,
        'version' => 3,
        'last_updated_by' => $this->user->id,
        'last_updated_at' => now(),
    ]);

    $this->olderVersion = EnvironmentSecretVersion::query()->create([
        'environment_secret_id' => $this->secret->id,
        'version' => 1,
        'name' => $this->variableName,
        'ciphertext' => base64_encode('first-version'),
        'nonce' => base64_encode(random_bytes(24)),
        'alg' => 'xchacha20-poly1305',
        'aad' => ['env' => (string) $this->environment->id],
        'claims' => ['hmac' => 'first-hmac'],
        'client_sig' => base64_encode(random_bytes(64)),
        'env_kek_version' => 1,
        'env_kek_fingerprint' => 'fingerprint-first',
        'metadata' => ['laravel' => ['is_vapor_secret' => false]],
        'line_bytes' => 16,
        'is_commented' => false,
        'created_at' => now()->subDays(3),
    ]);

    $this->secondVersion = EnvironmentSecretVersion::query()->create([
        'environment_secret_id' => $this->secret->id,
        'version' => 2,
        'name' => $this->variableName,
        'ciphertext' => base64_encode('second-version'),
        'nonce' => base64_encode(random_bytes(24)),
        'alg' => 'xchacha20-poly1305',
        'aad' => ['env' => (string) $this->environment->id],
        'claims' => ['hmac' => 'second-hmac'],
        'client_sig' => base64_encode(random_bytes(64)),
        'env_kek_version' => 2,
        'env_kek_fingerprint' => 'fingerprint-second',
        'metadata' => ['laravel' => ['is_vapor_secret' => true]],
        'line_bytes' => 24,
        'is_commented' => true,
        'created_at' => now()->subDay(),
    ]);

    $this->signPayload = function (array $payload): array {
        $payloadToSign = $payload;
        unset($payloadToSign['client_sig']);

        $payloadJson = json_encode(
            $payloadToSign,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );

        $payload['client_sig'] = base64_encode(
            sodium_crypto_sign_detached($payloadJson, $this->deviceSecretKey)
        );

        return $payload;
    };
});

test('a variable can be rolled back to a previous version', function (): void {
    Sanctum::actingAs($this->user);

    $payload = ($this->signPayload)([
        'device_id' => (string) $this->device->id,
        'version_id' => (string) $this->olderVersion->id,
        'if_version' => $this->secret->version,
    ]);

    $response = $this->postJson($this->endpoint, $payload);

    $response->assertOk()
        ->assertJsonPath('status', 'rolled_back')
        ->assertJsonPath('data.variable.name', $this->variableName)
        ->assertJsonPath('data.variable.rolled_back_to_version', $this->olderVersion->version)
        ->assertJsonPath('data.variable.version', $this->secret->version + 1)
        ->assertJsonPath('data.previous_head_version', $this->secret->version);

    $refreshed = $this->secret->fresh();

    expect($refreshed->version)->toBe($this->secret->version + 1);
    expect($refreshed->ciphertext)->toBe($this->olderVersion->ciphertext);
    expect($refreshed->env_kek_version)->toBe($this->olderVersion->env_kek_version);

    $newSnapshot = EnvironmentSecretVersion::query()
        ->where('environment_secret_id', $this->secret->id)
        ->where('version', $this->secret->version + 1)
        ->first();

    expect($newSnapshot)->not->toBeNull();
    expect($newSnapshot->ciphertext)->toBe($this->olderVersion->ciphertext);

    $activity = Activity::query()
        ->where('subject_type', $this->environment->getMorphClass())
        ->where('subject_id', $this->environment->id)
        ->where('event', 'rollback')
        ->first();

    expect($activity)->not->toBeNull();
    expect(data_get($activity->properties, 'variable.rolled_back_to_version'))->toBe($this->olderVersion->version);
});

test('rollback fails when the signature is invalid', function (): void {
    Sanctum::actingAs($this->user);

    $payload = [
        'device_id' => (string) $this->device->id,
        'version_id' => (string) $this->olderVersion->id,
        'if_version' => $this->secret->version,
        'client_sig' => base64_encode(Str::random(64)),
    ];

    $this->postJson($this->endpoint, $payload)
        ->assertStatus(422)
        ->assertJsonPath(
            'error.fields.client_sig.0',
            'Invalid signature detected for secret "variable rollback".'
        );
});

test('rollback rejects version mismatches', function (): void {
    Sanctum::actingAs($this->user);

    $payload = ($this->signPayload)([
        'device_id' => (string) $this->device->id,
        'version_id' => (string) $this->olderVersion->id,
        'if_version' => 999,
    ]);

    $this->postJson($this->endpoint, $payload)
        ->assertStatus(409)
        ->assertJsonPath('error.code', 'version_conflict')
        ->assertJsonPath('conflicts.0.key', $this->variableName)
        ->assertJsonPath('conflicts.0.server_version', 3)
        ->assertJsonPath('conflicts.0.client_if_version', 999);
});

test('rollback rejects versions that do not belong to the variable', function (): void {
    Sanctum::actingAs($this->user);

    $otherSecret = EnvironmentSecret::query()->create([
        'environment_id' => $this->environment->id,
        'name' => 'API_KEY',
        'ciphertext' => base64_encode('api-key-current'),
        'nonce' => base64_encode(random_bytes(24)),
        'alg' => 'xchacha20-poly1305',
        'aad' => ['env' => (string) $this->environment->id],
        'claims' => ['hmac' => 'api-key'],
        'client_sig' => base64_encode(random_bytes(64)),
        'env_kek_version' => 1,
        'env_kek_fingerprint' => 'fingerprint-api',
        'metadata' => [],
        'line_bytes' => 12,
        'is_commented' => false,
        'version' => 1,
        'last_updated_by' => $this->user->id,
        'last_updated_at' => now(),
    ]);

    $foreignVersion = EnvironmentSecretVersion::query()->create([
        'environment_secret_id' => $otherSecret->id,
        'version' => 1,
        'name' => $otherSecret->name,
        'ciphertext' => base64_encode('api-key'),
        'nonce' => base64_encode(random_bytes(24)),
        'alg' => 'xchacha20-poly1305',
        'aad' => ['env' => (string) $this->environment->id],
        'claims' => ['hmac' => 'api-key'],
        'client_sig' => base64_encode(random_bytes(64)),
        'env_kek_version' => 1,
        'env_kek_fingerprint' => 'fp',
        'metadata' => [],
        'line_bytes' => 12,
        'is_commented' => false,
        'created_at' => now(),
    ]);

    $payload = ($this->signPayload)([
        'device_id' => (string) $this->device->id,
        'version_id' => (string) $foreignVersion->id,
        'if_version' => $this->secret->version,
    ]);

    $this->postJson($this->endpoint, $payload)
        ->assertStatus(422)
        ->assertJsonPath('error.fields.version_id.0', 'The selected version does not belong to this variable.');
});
