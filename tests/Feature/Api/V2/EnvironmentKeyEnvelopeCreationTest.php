<?php

declare(strict_types=1);

use App\Core\Models\Activity;
use App\Crypto\Models\Device;
use App\Environment\Enums\EnvironmentType;
use App\Environment\Models\EnvironmentKey;
use Laravel\Sanctum\Sanctum;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = $this->createUser('Dana', 'dana@ghostbusters.com');
    $this->member = $this->createUser('Egon', 'egon@ghostbusters.com');
    $this->organization = $this->createOrganization('Ghostbusters', $this->user, [$this->member]);
    $this->project = $this->createProject('Containment Unit', $this->organization);
    $this->environment = $this->createEnvironment('production', EnvironmentType::PRODUCTION, $this->project);
    $this->environmentKey = EnvironmentKey::factory()
        ->forEnvironment($this->environment)
        ->create([
            'fingerprint' => hash('sha256', 'existing-key'),
        ]);

    $this->endpoint = sprintf(
        '/api/v2/projects/%s/environments/%s/key/envelopes',
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

test('an environment key envelope can be stored when signed by a device', function (): void {
    Sanctum::actingAs($this->user);

    $signingKeypair = sodium_crypto_sign_keypair();
    $signingSecretKey = sodium_crypto_sign_secretkey($signingKeypair);
    $signingPublicKey = sodium_crypto_sign_publickey($signingKeypair);

    $signingDevice = Device::factory()->for($this->user)->create([
        'active' => true,
        'revoked_at' => null,
        'public_signing_key' => base64_encode($signingPublicKey),
    ]);

    $recipientDevice = Device::factory()->for($this->member)->create([
        'active' => true,
        'revoked_at' => null,
    ]);

    $recipientPayload = [
        'ciphertext_b64' => base64_encode(random_bytes(32)),
        'nonce_b64' => base64_encode(random_bytes(24)),
        'alg' => 'xchacha20-poly1305',
        'version' => '1',
    ];

    $payload = [
        'device_id' => (string) $signingDevice->getKey(),
        'fingerprint' => $this->environmentKey->fingerprint,
        'envelope' => [
            'ciphertext_b64' => base64_encode(random_bytes(32)),
            'nonce_b64' => base64_encode(random_bytes(24)),
            'recipients' => [
                [
                    'type' => 'device',
                    'id' => (string) $recipientDevice->getKey(),
                    'edek_b64' => 'b64:'.base64_encode(json_encode($recipientPayload, JSON_THROW_ON_ERROR)),
                ],
            ],
        ],
    ];

    $payload = ($this->signPayload)($payload, $signingSecretKey);

    $this->postJson($this->endpoint, $payload)
        ->assertOk()
        ->assertJsonPath('data.relationships.envelope.data.attributes.ciphertext_b64', $payload['envelope']['ciphertext_b64']);

    $activity = Activity::query()
        ->where('event', 'environment_key_reshared')
        ->latest('id')
        ->first();

    expect($activity)->not()->toBeNull();
    expect((string) $activity->subject_id)->toBe((string) $this->environment->getKey());
    expect(data_get($activity?->properties, 'recipient_counts.device'))->toBe(1);
});

test('environment key envelope creation fails with an invalid signature', function (): void {
    Sanctum::actingAs($this->user);

    $signingKeypair = sodium_crypto_sign_keypair();
    $signingPublicKey = sodium_crypto_sign_publickey($signingKeypair);

    $signingDevice = Device::factory()->for($this->user)->create([
        'active' => true,
        'revoked_at' => null,
        'public_signing_key' => base64_encode($signingPublicKey),
    ]);

    $payload = [
        'device_id' => (string) $signingDevice->getKey(),
        'fingerprint' => $this->environmentKey->fingerprint,
        'envelope' => [
            'ciphertext_b64' => base64_encode(random_bytes(32)),
            'nonce_b64' => base64_encode(random_bytes(24)),
        ],
        'client_sig' => base64_encode(str_repeat('x', 32)),
    ];

    $response = $this->postJson($this->endpoint, $payload);

    $response->assertStatus(422)
        ->assertJsonPath('error.fields.client_sig.0', 'Invalid signature format.');
});
