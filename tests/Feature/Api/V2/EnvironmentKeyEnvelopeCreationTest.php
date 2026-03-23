<?php

declare(strict_types=1);

use App\Core\Models\Activity;
use App\Crypto\Models\Device;
use App\Environment\Enums\EnvironmentKeyReshareRequestStatus;
use App\Environment\Enums\EnvironmentType;
use App\Environment\Models\EnvironmentKeyReshareRequest;
use App\Organization\Notifications\EnvironmentKeyReshareCompletedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = $this->createUser('Dana', 'dana@ghostbusters.com');
    $this->member = $this->createUser('Egon', 'egon@ghostbusters.com');
    $this->organization = $this->createOrganization('Ghostbusters', $this->user, [$this->member]);
    $this->project = $this->createProject('Containment Unit', $this->organization);
    $this->environment = $this->createEnvironment('production', EnvironmentType::PRODUCTION, $this->project);

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

    $this->environmentKey = $this->createEnvironmentKeyWithEnvelope(
        $this->environment,
        $signingDevice
    );

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
    expect(data_get($activity?->properties, 'recipient_counts.device'))->toBe(1)
        ->and(data_get($activity?->properties, 'recipient_diff.device.before_count'))->toBe(1)
        ->and(data_get($activity?->properties, 'recipient_diff.device.after_count'))->toBe(1)
        ->and(data_get($activity?->properties, 'recipient_diff.device.added.0.id'))->toBe((string) $recipientDevice->getKey())
        ->and(data_get($activity?->properties, 'recipient_diff.device.removed.0.id'))->toBe((string) $signingDevice->getKey());
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

    $this->environmentKey = $this->createEnvironmentKeyWithEnvelope(
        $this->environment,
        $signingDevice
    );

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

test('request_ids can be provided and complete matching pending requests', function (): void {
    Sanctum::actingAs($this->user);
    Notification::fake();

    $signingKeypair = sodium_crypto_sign_keypair();
    $signingSecretKey = sodium_crypto_sign_secretkey($signingKeypair);
    $signingPublicKey = sodium_crypto_sign_publickey($signingKeypair);

    $signingDevice = Device::factory()->for($this->user)->create([
        'active' => true,
        'revoked_at' => null,
        'public_signing_key' => base64_encode($signingPublicKey),
    ]);

    $this->environmentKey = $this->createEnvironmentKeyWithEnvelope(
        $this->environment,
        $signingDevice
    );

    $recipientDevice = Device::factory()->for($this->member)->create([
        'active' => true,
        'revoked_at' => null,
    ]);

    $pendingRequest = EnvironmentKeyReshareRequest::query()->create([
        'organization_id' => $this->organization->id,
        'project_id' => $this->project->id,
        'environment_id' => $this->environment->id,
        'required_key_version' => $this->environmentKey->version,
        'target_user_id' => $this->member->id,
        'target_device_id' => $recipientDevice->id,
        'status' => EnvironmentKeyReshareRequestStatus::Pending,
        'trigger_source' => 'device_link',
    ]);

    $payload = [
        'device_id' => (string) $signingDevice->getKey(),
        'fingerprint' => $this->environmentKey->fingerprint,
        'request_ids' => [(string) $pendingRequest->id],
        'envelope' => [
            'ciphertext_b64' => base64_encode(random_bytes(32)),
            'nonce_b64' => base64_encode(random_bytes(24)),
            'recipients' => [
                [
                    'type' => 'device',
                    'id' => (string) $recipientDevice->getKey(),
                    'edek_b64' => 'b64:'.base64_encode(json_encode([
                        'ciphertext_b64' => base64_encode(random_bytes(32)),
                        'nonce_b64' => base64_encode(random_bytes(24)),
                        'alg' => 'xchacha20-poly1305',
                        'version' => '1',
                    ], JSON_THROW_ON_ERROR)),
                ],
            ],
        ],
    ];

    $payload = ($this->signPayload)($payload, $signingSecretKey);

    $this->postJson($this->endpoint, $payload)->assertOk();

    $pendingRequest->refresh();

    expect($pendingRequest->status)->toBe(EnvironmentKeyReshareRequestStatus::Completed)
        ->and((string) $pendingRequest->resolved_by_user_id)->toBe((string) $this->user->id)
        ->and($pendingRequest->resolved_at)->not()->toBeNull();

    Notification::assertSentTo($this->member, EnvironmentKeyReshareCompletedNotification::class);
});

test('request_ids alone cannot complete pending requests without matching device recipients', function (): void {
    Sanctum::actingAs($this->user);
    Notification::fake();

    $signingKeypair = sodium_crypto_sign_keypair();
    $signingSecretKey = sodium_crypto_sign_secretkey($signingKeypair);
    $signingPublicKey = sodium_crypto_sign_publickey($signingKeypair);

    $signingDevice = Device::factory()->for($this->user)->create([
        'active' => true,
        'revoked_at' => null,
        'public_signing_key' => base64_encode($signingPublicKey),
    ]);

    $this->environmentKey = $this->createEnvironmentKeyWithEnvelope(
        $this->environment,
        $signingDevice
    );

    $recipientDevice = Device::factory()->for($this->member)->create([
        'active' => true,
        'revoked_at' => null,
    ]);

    $pendingRequest = EnvironmentKeyReshareRequest::query()->create([
        'organization_id' => $this->organization->id,
        'project_id' => $this->project->id,
        'environment_id' => $this->environment->id,
        'required_key_version' => $this->environmentKey->version,
        'target_user_id' => $this->member->id,
        'target_device_id' => $recipientDevice->id,
        'status' => EnvironmentKeyReshareRequestStatus::Pending,
        'trigger_source' => 'device_link',
    ]);

    $payload = [
        'device_id' => (string) $signingDevice->getKey(),
        'fingerprint' => $this->environmentKey->fingerprint,
        'request_ids' => [(string) $pendingRequest->id],
        'envelope' => [
            'ciphertext_b64' => base64_encode(random_bytes(32)),
            'nonce_b64' => base64_encode(random_bytes(24)),
        ],
    ];

    $payload = ($this->signPayload)($payload, $signingSecretKey);

    $this->postJson($this->endpoint, $payload)->assertOk();

    $pendingRequest->refresh();

    expect($pendingRequest->status)->toBe(EnvironmentKeyReshareRequestStatus::Pending)
        ->and($pendingRequest->resolved_at)->toBeNull()
        ->and($pendingRequest->resolved_by_user_id)->toBeNull();

    Notification::assertNothingSent();
});

test('environment key envelope creation fails when signing device is not already a key recipient', function (): void {
    Sanctum::actingAs($this->user);

    $authorizedKeypair = sodium_crypto_sign_keypair();
    $authorizedPublicKey = sodium_crypto_sign_publickey($authorizedKeypair);
    $authorizedDevice = Device::factory()->for($this->user)->create([
        'active' => true,
        'revoked_at' => null,
        'public_signing_key' => base64_encode($authorizedPublicKey),
    ]);

    $this->environmentKey = $this->createEnvironmentKeyWithEnvelope(
        $this->environment,
        $authorizedDevice
    );

    $unauthorizedKeypair = sodium_crypto_sign_keypair();
    $unauthorizedSecretKey = sodium_crypto_sign_secretkey($unauthorizedKeypair);
    $unauthorizedPublicKey = sodium_crypto_sign_publickey($unauthorizedKeypair);
    $unauthorizedDevice = Device::factory()->for($this->user)->create([
        'active' => true,
        'revoked_at' => null,
        'public_signing_key' => base64_encode($unauthorizedPublicKey),
    ]);

    $payload = [
        'device_id' => (string) $unauthorizedDevice->getKey(),
        'fingerprint' => $this->environmentKey->fingerprint,
        'envelope' => [
            'ciphertext_b64' => base64_encode(random_bytes(32)),
            'nonce_b64' => base64_encode(random_bytes(24)),
        ],
    ];

    $payload = ($this->signPayload)($payload, $unauthorizedSecretKey);

    $this->postJson($this->endpoint, $payload)
        ->assertStatus(422)
        ->assertJsonPath(
            'error.fields.device_id.0',
            'This device cannot fulfill key re-share requests for the selected environment key.'
        );
});
