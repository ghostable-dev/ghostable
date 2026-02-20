<?php

declare(strict_types=1);

use App\Core\Models\Activity;
use App\Crypto\Models\Device;
use App\Environment\Enums\EnvironmentType;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = $this->createUser('Dana', 'dana@ghostbusters.com');
    $this->member = $this->createUser('Egon', 'egon@ghostbusters.com');
    $this->organization = $this->createOrganization('Ghostbusters', $this->user, [$this->member]);
    $this->project = $this->createProject('Containment Unit', $this->organization);
    $this->environment = $this->createEnvironment('production', EnvironmentType::PRODUCTION, $this->project);
    $this->endpoint = sprintf(
        '/api/v2/projects/%s/environments/%s/key',
        $this->project->getKey(),
        $this->environment->name
    );

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

test('environment key can be created for multiple devices', function (): void {
    Sanctum::actingAs($this->user);

    $creatorKeypair = sodium_crypto_sign_keypair();
    $creatorSecretKey = sodium_crypto_sign_secretkey($creatorKeypair);
    $creatorPublicKey = sodium_crypto_sign_publickey($creatorKeypair);

    $creatorDevice = Device::factory()->for($this->user)->create([
        'active' => true,
        'revoked_at' => null,
        'public_signing_key' => base64_encode($creatorPublicKey),
    ]);

    $memberDevice = Device::factory()->for($this->member)->create([
        'active' => true,
        'revoked_at' => null,
    ]);

    $payload = [
        'device_id' => (string) $creatorDevice->getKey(),
        'fingerprint' => hash('sha256', 'new-key'),
        'created_by_device_id' => (string) $creatorDevice->getKey(),
        'envelopes' => [
            [
                'device_id' => (string) $creatorDevice->getKey(),
                'ciphertext_b64' => base64_encode(random_bytes(32)),
                'nonce_b64' => base64_encode(random_bytes(24)),
                'from_ephemeral_public_key' => 'ephemeral-public-key-1',
                'expires_at' => Carbon::now()->addHour()->toIso8601String(),
            ],
            [
                'device_id' => (string) $memberDevice->getKey(),
                'ciphertext_b64' => base64_encode(random_bytes(32)),
                'nonce_b64' => base64_encode(random_bytes(24)),
                'from_ephemeral_public_key' => 'ephemeral-public-key-2',
            ],
        ],
    ];

    $payload = ($this->signPayload)($payload, $creatorSecretKey);

    $response = $this->postJson($this->endpoint, $payload);

    $response->assertCreated()
        ->assertJsonPath('data.type', 'environment-keys')
        ->assertJsonPath('data.attributes.environment_id', (string) $this->environment->getKey())
        ->assertJsonPath('data.attributes.fingerprint', $payload['fingerprint'])
        ->assertJsonPath('data.attributes.created_by_device_id', (string) $creatorDevice->getKey())
        ->assertJsonCount(2, 'data.relationships.envelopes.data');

    $envelopes = $response->json('data.relationships.envelopes.data');

    expect($envelopes[0]['attributes']['device_id'])->toBe((string) $creatorDevice->getKey());
    expect($envelopes[1]['attributes']['device_id'])->toBe((string) $memberDevice->getKey());

    $activity = Activity::query()
        ->where('event', 'environment_key_created')
        ->latest('id')
        ->first();

    expect($activity)->not()->toBeNull();
    expect((string) $activity->subject_id)->toBe((string) $this->environment->getKey());
    expect(data_get($activity?->properties, 'recipient_counts.device'))->toBe(2);
});

test('environment key signature verification uses the raw payload order', function (): void {
    Sanctum::actingAs($this->user);

    $creatorKeypair = sodium_crypto_sign_keypair();
    $creatorSecretKey = sodium_crypto_sign_secretkey($creatorKeypair);
    $creatorPublicKey = sodium_crypto_sign_publickey($creatorKeypair);

    $creatorDevice = Device::factory()->for($this->user)->create([
        'active' => true,
        'revoked_at' => null,
        'public_signing_key' => base64_encode($creatorPublicKey),
    ]);

    $memberDevice = Device::factory()->for($this->member)->create([
        'active' => true,
        'revoked_at' => null,
    ]);

    $payloadForSigning = [
        'fingerprint' => hash('sha256', 'unordered-key'),
        'rotated_at' => Carbon::now()->addDay()->toIso8601String(),
        'device_id' => (string) $creatorDevice->getKey(),
        'created_by_device_id' => (string) $creatorDevice->getKey(),
        'version' => 3,
        'envelopes' => [
            [
                'device_id' => (string) $creatorDevice->getKey(),
                'ciphertext_b64' => base64_encode(random_bytes(32)),
                'nonce_b64' => base64_encode(random_bytes(24)),
                'from_ephemeral_public_key' => 'ephemeral-a',
            ],
            [
                'device_id' => (string) $memberDevice->getKey(),
                'nonce_b64' => base64_encode(random_bytes(24)),
                'ciphertext_b64' => base64_encode(random_bytes(32)),
                'from_ephemeral_public_key' => 'ephemeral-b',
            ],
        ],
    ];

    $signedPayload = ($this->signPayload)($payloadForSigning, $creatorSecretKey);

    $this->postJson($this->endpoint, $signedPayload)
        ->assertCreated()
        ->assertJsonPath('data.attributes.version', 3);
});

test('environment key creation fails with an invalid signature', function (): void {
    Sanctum::actingAs($this->user);

    $creatorKeypair = sodium_crypto_sign_keypair();
    $creatorPublicKey = sodium_crypto_sign_publickey($creatorKeypair);

    $creatorDevice = Device::factory()->for($this->user)->create([
        'active' => true,
        'revoked_at' => null,
        'public_signing_key' => base64_encode($creatorPublicKey),
    ]);

    $payload = [
        'device_id' => (string) $creatorDevice->getKey(),
        'fingerprint' => hash('sha256', 'new-key'),
        'created_by_device_id' => (string) $creatorDevice->getKey(),
        'envelopes' => [
            [
                'device_id' => (string) $creatorDevice->getKey(),
                'ciphertext_b64' => base64_encode(random_bytes(32)),
                'nonce_b64' => base64_encode(random_bytes(24)),
            ],
        ],
        'client_sig' => base64_encode(str_repeat('x', 32)),
    ];

    $response = $this->postJson($this->endpoint, $payload);

    $response->assertStatus(422)
        ->assertJsonPath('error.fields.client_sig.0', 'Invalid signature format.');
});

test('users without manage permissions cannot create environment keys', function (): void {
    $externalUser = $this->createUser('Louis', 'louis@tully.com');
    Sanctum::actingAs($externalUser);

    $device = Device::factory()->for($externalUser)->create([
        'active' => true,
        'revoked_at' => null,
    ]);

    $payload = [
        'device_id' => (string) $device->getKey(),
        'fingerprint' => hash('sha256', 'new-key'),
        'envelopes' => [
            [
                'device_id' => (string) $device->getKey(),
                'ciphertext_b64' => base64_encode(random_bytes(32)),
                'nonce_b64' => base64_encode(random_bytes(24)),
                'from_ephemeral_public_key' => 'ephemeral-public-key-1',
            ],
        ],
        'client_sig' => base64_encode('unsigned'),
    ];

    $this->postJson($this->endpoint, $payload)->assertForbidden();
});

test('devices outside the organization cannot be used for envelopes', function (): void {
    Sanctum::actingAs($this->user);

    $creatorKeypair = sodium_crypto_sign_keypair();
    $creatorSecretKey = sodium_crypto_sign_secretkey($creatorKeypair);
    $creatorPublicKey = sodium_crypto_sign_publickey($creatorKeypair);

    $creatorDevice = Device::factory()->for($this->user)->create([
        'active' => true,
        'revoked_at' => null,
        'public_signing_key' => base64_encode($creatorPublicKey),
    ]);

    $externalUser = $this->createUser('Walter', 'walter@epa.gov');
    $externalDevice = Device::factory()->for($externalUser)->create([
        'active' => true,
        'revoked_at' => null,
    ]);

    $payload = [
        'device_id' => (string) $creatorDevice->getKey(),
        'fingerprint' => hash('sha256', 'new-key'),
        'created_by_device_id' => (string) $creatorDevice->getKey(),
        'envelopes' => [
            [
                'device_id' => (string) $creatorDevice->getKey(),
                'ciphertext_b64' => base64_encode(random_bytes(32)),
                'nonce_b64' => base64_encode(random_bytes(24)),
                'from_ephemeral_public_key' => 'ephemeral-public-key-1',
            ],
            [
                'device_id' => (string) $externalDevice->getKey(),
                'ciphertext_b64' => base64_encode(random_bytes(32)),
                'nonce_b64' => base64_encode(random_bytes(24)),
                'from_ephemeral_public_key' => 'ephemeral-public-key-2',
            ],
        ],
    ];

    $payload = ($this->signPayload)($payload, $creatorSecretKey);

    $this->postJson($this->endpoint, $payload)->assertUnprocessable();
});
