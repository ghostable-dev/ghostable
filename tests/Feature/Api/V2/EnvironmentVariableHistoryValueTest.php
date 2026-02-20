<?php

declare(strict_types=1);

use App\Crypto\Models\Device;
use App\Environment\Enums\EnvironmentType;
use App\Environment\Models\EnvironmentKey;
use App\Environment\Models\EnvironmentSecret;
use App\Environment\Models\EnvironmentSecretVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = $this->createUser('Dana', 'dana@example.com');
    $this->organization = $this->createOrganization('Ghostbusters', $this->user);
    $this->project = $this->createProject('Containment Unit', $this->organization);
    $this->environment = $this->createEnvironment('production', EnvironmentType::PRODUCTION, $this->project);

    $this->variableName = 'DB_PASSWORD';

    $this->secret = EnvironmentSecret::query()->create([
        'environment_id' => $this->environment->id,
        'name' => $this->variableName,
        'ciphertext' => base64_encode('current-secret'),
        'nonce' => base64_encode(random_bytes(24)),
        'alg' => 'xchacha20-poly1305',
        'aad' => ['env' => (string) $this->environment->id],
        'claims' => ['hmac' => 'current-hmac'],
        'client_sig' => base64_encode(random_bytes(64)),
        'env_kek_version' => 2,
        'env_kek_fingerprint' => 'fingerprint-current',
        'metadata' => ['laravel' => ['is_vapor_secret' => false]],
        'line_bytes' => 32,
        'is_commented' => false,
        'version' => 2,
        'last_updated_by' => $this->user->id,
        'last_updated_at' => now(),
    ]);

    $this->firstVersion = EnvironmentSecretVersion::query()->create([
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
        'changed_by' => $this->user->id,
        'created_at' => now()->subDays(2),
    ]);

    $this->requestDevice = Device::factory()->for($this->user)->create([
        'platform' => 'macos',
        'client_type' => 'desktop',
    ]);

    $this->otherDevice = Device::factory()->for($this->user)->create([
        'platform' => 'linux',
        'client_type' => 'cli',
    ]);

    $this->environmentKey = EnvironmentKey::factory()
        ->forEnvironment($this->environment)
        ->version(1)
        ->create([
            'fingerprint' => 'fingerprint-first',
            'created_by_device_id' => $this->requestDevice->id,
        ]);

    $this->environmentKey->envelope()->create([
        'ciphertext_b64' => base64_encode(random_bytes(64)),
        'nonce_b64' => base64_encode(random_bytes(24)),
        'alg' => 'xchacha20-poly1305',
        'version' => '1',
        'recipients' => [
            [
                'type' => 'device',
                'id' => (string) $this->requestDevice->getKey(),
                'edek_b64' => base64_encode(json_encode([
                    'ciphertext_b64' => base64_encode(random_bytes(24)),
                    'nonce_b64' => base64_encode(random_bytes(24)),
                    'alg' => 'xchacha20-poly1305',
                    'version' => '1',
                ], JSON_THROW_ON_ERROR)),
            ],
            [
                'type' => 'device',
                'id' => (string) $this->otherDevice->getKey(),
                'edek_b64' => base64_encode(json_encode([
                    'ciphertext_b64' => base64_encode(random_bytes(24)),
                    'nonce_b64' => base64_encode(random_bytes(24)),
                    'alg' => 'xchacha20-poly1305',
                    'version' => '1',
                ], JSON_THROW_ON_ERROR)),
            ],
        ],
    ]);

    $this->endpoint = function (string $versionId): string {
        return sprintf(
            '/api/v2/projects/%s/environments/%s/variables/%s/history/%s/value',
            $this->project->id,
            $this->environment->name,
            $this->variableName,
            $versionId
        );
    };
});

test('users can retrieve an encrypted historical version value for their own device', function (): void {
    Sanctum::actingAs($this->user);

    $response = $this->getJson(
        ($this->endpoint)((string) $this->firstVersion->id).'?device_id='.$this->requestDevice->id
    );

    $response->assertOk()
        ->assertJsonPath('data.scope', 'variable_version_value')
        ->assertJsonPath('data.version_id', (string) $this->firstVersion->id)
        ->assertJsonPath('data.variable', $this->variableName)
        ->assertJsonPath('data.secret.ciphertext', $this->firstVersion->ciphertext)
        ->assertJsonPath('data.secret.env_kek_fingerprint', 'fingerprint-first')
        ->assertJsonPath('data.environment_key.id', (string) $this->environmentKey->id)
        ->assertJsonCount(1, 'data.environment_key.relationships.envelope.data.attributes.recipients')
        ->assertJsonPath('data.environment_key.relationships.envelope.data.attributes.recipients.0.id', (string) $this->requestDevice->id)
        ->assertJsonCount(1, 'data.environment_key.relationships.envelopes.data')
        ->assertJsonPath('data.environment_key.relationships.envelopes.data.0.id', (string) $this->requestDevice->id);
});

test('endpoint requires a device id', function (): void {
    Sanctum::actingAs($this->user);

    $this->getJson(($this->endpoint)((string) $this->firstVersion->id))
        ->assertStatus(422)
        ->assertJsonPath('error.fields.device_id.0', 'The device_id query parameter is required.');
});

test('users cannot request history values with another users device id', function (): void {
    $otherUser = $this->createUser('Walter', 'walter@epa.gov');
    $foreignDevice = Device::factory()->for($otherUser)->create();

    Sanctum::actingAs($this->user);

    $this->getJson(($this->endpoint)((string) $this->firstVersion->id).'?device_id='.$foreignDevice->id)
        ->assertForbidden();
});

test('users without permission cannot retrieve history values', function (): void {
    $outsider = $this->createUser('Louis', 'louis@example.com');
    Sanctum::actingAs($outsider);

    $this->getJson(($this->endpoint)((string) $this->firstVersion->id).'?device_id='.$this->requestDevice->id)
        ->assertForbidden();
});
