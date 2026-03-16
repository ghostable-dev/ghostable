<?php

declare(strict_types=1);

use App\Crypto\Models\Device;
use App\Environment\Actions\Token\CreateDeploymentToken as CreateDeploymentTokenAction;
use App\Environment\Enums\EnvironmentType;
use App\Environment\Models\EnvironmentKey;
use App\Organization\Enums\OrganizationRole;
use Illuminate\Support\Arr;
use Laravel\Sanctum\Sanctum;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = $this->createUser('Dana', 'dana@ghostbusters.com');
    $this->organization = $this->createOrganization('Ghostbusters', $this->user);
    $this->project = $this->createProject('Containment Unit', $this->organization);
    $this->environment = $this->createEnvironment('production', EnvironmentType::PRODUCTION, $this->project);
    $this->endpoint = sprintf(
        '/api/v2/projects/%s/environments/%s/key',
        $this->project->getKey(),
        $this->environment->name
    );

    $signingKeypair = sodium_crypto_sign_keypair();
    $this->signingSecretKey = sodium_crypto_sign_secretkey($signingKeypair);
    $signingPublicKey = sodium_crypto_sign_publickey($signingKeypair);

    $this->signingDevice = Device::factory()->for($this->user)->create([
        'active' => true,
        'revoked_at' => null,
        'public_signing_key' => base64_encode($signingPublicKey),
    ]);

    $this->signPayload = function (array $payload, string $secretKey): array {
        $payloadToSign = $payload;
        unset($payloadToSign['client_sig']);

        $payloadJson = json_encode(
            $payloadToSign,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );

        $payload['client_sig'] = base64_encode(
            sodium_crypto_sign_detached($payloadJson, $secretKey)
        );

        return $payload;
    };
});

test('latest environment key with active envelopes is returned', function (): void {
    Sanctum::actingAs($this->user);

    $device = Device::factory()->for($this->user)->create();

    $deploymentToken = app(CreateDeploymentTokenAction::class)->handle(
        name: 'Pipeline deploy token',
        environment: $this->environment,
        publicKey: base64_encode(random_bytes(32)),
        user: $this->user,
    )->token;

    EnvironmentKey::factory()
        ->forEnvironment($this->environment)
        ->version(1)
        ->create([
            'fingerprint' => hash('sha256', 'old-key'),
        ]);

    $latestKey = EnvironmentKey::factory()
        ->forEnvironment($this->environment)
        ->version(2)
        ->createdBy($device)
        ->create([
            'fingerprint' => hash('sha256', 'latest-key'),
        ]);

    $latestKey->envelope()->create([
        'ciphertext_b64' => base64_encode(random_bytes(64)),
        'nonce_b64' => base64_encode(random_bytes(12)),
        'alg' => 'xchacha20-poly1305',
        'version' => '2',
        'recipients' => [
            [
                'type' => 'device',
                'id' => (string) $device->getKey(),
                'edek_b64' => base64_encode(random_bytes(32)),
            ],
            [
                'type' => 'deployment',
                'id' => (string) $deploymentToken->getKey(),
                'edek_b64' => base64_encode(random_bytes(32)),
            ],
        ],
    ]);

    $response = $this->getJson($this->endpoint);

    $response->assertOk()
        ->assertJsonPath('data.type', 'environment-keys')
        ->assertJsonPath('data.id', (string) $latestKey->getKey())
        ->assertJsonPath('data.attributes.version', 2)
        ->assertJsonPath('data.attributes.fingerprint', hash('sha256', 'latest-key'))
        ->assertJsonPath('data.attributes.created_by_device_id', (string) $device->getKey())
        ->assertJsonPath('data.relationships.envelope.data.type', 'encrypted-envelopes');

    $envelopeAttributes = Arr::dot($response->json('data.relationships.envelope.data.attributes'));

    expect($envelopeAttributes)
        ->toHaveKey('ciphertext_b64')
        ->toHaveKey('nonce_b64')
        ->toHaveKey('recipients.0.id', (string) $device->getKey())
        ->toHaveKey('recipients.1.type', 'deployment')
        ->toHaveKey('recipients.1.id', (string) $deploymentToken->getKey());
});

test('null data is returned when no environment keys exist', function (): void {
    Sanctum::actingAs($this->user);

    $response = $this->getJson($this->endpoint);

    $response->assertOk()
        ->assertJsonPath('data', null);
});

test('users without access cannot retrieve environment keys', function (): void {
    $otherUser = $this->createUser('Walter', 'walter@epa.gov');
    Sanctum::actingAs($otherUser);

    $this->getJson($this->endpoint)->assertForbidden();
});

test('returns ENV_KEY_RESHARE_REQUIRED when the linked device is missing key access', function (): void {
    $recipient = $this->createUser('Louis', 'louis@ghostbusters.com');
    $recipient->organizationMembership()->assignToOrganization($this->organization, OrganizationRole::DEVELOPER);

    $recipientDevice = Device::factory()->for($recipient)->create([
        'active' => true,
        'revoked_at' => null,
        'public_signing_key' => base64_encode(random_bytes(32)),
    ]);

    $this->organization->features = $this->organization->features->withOverrides([
        'guided_key_reshare_v2' => true,
    ]);
    $this->organization->save();

    $environmentKey = $this->createEnvironmentKeyWithEnvelope(
        environment: $this->environment,
        createdByDevice: $this->signingDevice,
        recipients: [
            [
                'id' => (string) $this->signingDevice->id,
                'type' => 'device',
                'label' => 'Owner device',
            ],
        ],
    );
    $environmentKey->forceFill([
        'rotated_at' => now()->subDay(),
    ])->save();

    Sanctum::actingAs($recipient);

    $response = $this->getJson("{$this->endpoint}?device_id={$recipientDevice->id}");

    $response->assertStatus(409)
        ->assertJsonPath('error.code', 'ENV_KEY_RESHARE_REQUIRED')
        ->assertJsonPath('error.required_key_version', $environmentKey->version)
        ->assertJsonPath('error.environment_id', (string) $this->environment->id)
        ->assertJsonPath('error.organization_id', (string) $this->organization->id);

    $pendingRequestId = data_get($response->json(), 'error.pending_request_ids.0');

    expect($pendingRequestId)->toBeString()->not->toBe('');

    $this->assertDatabaseHas('environment_key_reshare_requests', [
        'id' => $pendingRequestId,
        'organization_id' => (string) $this->organization->id,
        'environment_id' => (string) $this->environment->id,
        'target_user_id' => (string) $recipient->id,
        'target_device_id' => (string) $recipientDevice->id,
        'required_key_version' => $environmentKey->version,
        'status' => 'pending',
    ]);
});

test('creating environment keys normalizes deployment token recipients', function (): void {
    Sanctum::actingAs($this->user);

    $deploymentToken = app(CreateDeploymentTokenAction::class)->handle(
        name: 'CLI token',
        environment: $this->environment,
        publicKey: base64_encode(random_bytes(32)),
        user: $this->user,
    )->token;

    $payload = [
        'device_id' => (string) $this->signingDevice->getKey(),
        'fingerprint' => hash('sha256', 'fresh-key'),
        'envelope' => [
            'ciphertext_b64' => base64_encode(random_bytes(48)),
            'nonce_b64' => base64_encode(random_bytes(12)),
            'recipients' => [
                [
                    'type' => 'deployment',
                    'id' => (string) $deploymentToken->getKey(),
                    'edek_b64' => base64_encode(random_bytes(32)),
                ],
            ],
        ],
    ];

    $payload = ($this->signPayload)($payload, $this->signingSecretKey);

    $response = $this->postJson(
        sprintf('/api/v2/projects/%s/environments/%s/key', $this->project->getKey(), $this->environment->name),
        $payload
    );

    $response->assertCreated()
        ->assertJsonPath('data.relationships.envelope.data.attributes.recipients.0.type', 'deployment');

    $environmentKey = EnvironmentKey::query()->where('fingerprint', $payload['fingerprint'])->firstOrFail();
    $recipientType = $environmentKey->envelope->recipients[0]['type'] ?? null;

    expect($recipientType)->toBe('deployment');
});

test('updating environment key envelopes normalizes deployment token recipients', function (): void {
    Sanctum::actingAs($this->user);

    $deploymentToken = app(CreateDeploymentTokenAction::class)->handle(
        name: 'CI token',
        environment: $this->environment,
        publicKey: base64_encode(random_bytes(32)),
        user: $this->user,
    )->token;

    $environmentKey = EnvironmentKey::factory()
        ->forEnvironment($this->environment)
        ->version(1)
        ->create([
            'fingerprint' => hash('sha256', 'existing-key'),
        ]);

    $environmentKey->envelope()->create([
        'ciphertext_b64' => base64_encode(random_bytes(48)),
        'nonce_b64' => base64_encode(random_bytes(12)),
        'alg' => 'xchacha20-poly1305',
        'version' => '1',
        'recipients' => [
            [
                'type' => 'device',
                'id' => (string) $this->signingDevice->getKey(),
                'edek_b64' => base64_encode(random_bytes(32)),
            ],
        ],
    ]);

    $payload = [
        'device_id' => (string) $this->signingDevice->getKey(),
        'fingerprint' => $environmentKey->fingerprint,
        'envelope' => [
            'ciphertext_b64' => base64_encode(random_bytes(48)),
            'nonce_b64' => base64_encode(random_bytes(12)),
            'recipients' => [
                [
                    'type' => 'deployment',
                    'id' => (string) $deploymentToken->getKey(),
                    'edek_b64' => base64_encode(random_bytes(32)),
                ],
            ],
        ],
    ];

    $payload = ($this->signPayload)($payload, $this->signingSecretKey);

    $response = $this->postJson(
        sprintf('/api/v2/projects/%s/environments/%s/key/envelopes', $this->project->getKey(), $this->environment->name),
        $payload
    );

    $response->assertOk()
        ->assertJsonPath('data.relationships.envelope.data.attributes.recipients.0.type', 'deployment');

    $environmentKey->refresh();
    $recipientType = $environmentKey->envelope->recipients[0]['type'] ?? null;

    expect($recipientType)->toBe('deployment');
});
