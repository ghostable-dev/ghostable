<?php

declare(strict_types=1);

use App\Crypto\Models\Device;
use App\Environment\Enums\EnvironmentType;
use App\Environment\Enums\EnvironmentVariablePromotionRequestStatus;
use App\Environment\Models\EnvironmentVariablePromotionRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = $this->createUser('Dana', 'dana@ghostbusters.com');
    $this->organization = $this->createOrganization('Ghostbusters', $this->user);
    $this->project = $this->createProject('Containment Unit', $this->organization);
    $this->sourceEnvironment = $this->createEnvironment('local', EnvironmentType::LOCAL, $this->project);
    $this->targetEnvironment = $this->createEnvironment('production', EnvironmentType::PRODUCTION, $this->project);

    $keypair = sodium_crypto_sign_keypair();
    $this->deviceSecretKey = sodium_crypto_sign_secretkey($keypair);
    $devicePublicKey = sodium_crypto_sign_publickey($keypair);

    $this->device = Device::factory()->for($this->user)->create([
        'active' => true,
        'revoked_at' => null,
        'public_signing_key' => base64_encode($devicePublicKey),
    ]);

    $this->baseEndpoint = sprintf(
        '/api/v2/projects/%s/environments/%s',
        $this->project->getKey(),
        $this->sourceEnvironment->name
    );

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

    $this->buildEntry = function (bool $sourceValuePresent = true): array {
        $payload = [
            'env' => $this->targetEnvironment->name,
            'name' => 'APP_DEBUG',
            'ciphertext' => base64_encode(random_bytes(32)),
            'nonce' => base64_encode(random_bytes(24)),
            'alg' => 'xchacha20-poly1305',
            'aad' => ['scope' => 'promotion-test'],
            'claims' => ['meta' => ['origin' => 'test-suite']],
            'if_version' => 1,
            'line_bytes' => 4,
            'is_commented' => false,
            'env_kek_version' => 1,
            'env_kek_fingerprint' => 'fp-test',
        ];

        $signedPayload = ($this->signPayload)($payload);

        return [
            'name' => 'APP_DEBUG',
            'source_if_version' => 1,
            'line_bytes' => 4,
            'is_commented' => false,
            'source_value_present' => $sourceValuePresent,
            'payload' => $signedPayload,
        ];
    };
});

test('promotion request lifecycle supports create list show and cancel', function (): void {
    Sanctum::actingAs($this->user);

    $createResponse = $this->withHeaders([
        'X-Idempotency-Key' => 'promotion-lifecycle-001',
    ])->postJson("{$this->baseEndpoint}/promotion-requests", [
        'device_id' => (string) $this->device->getKey(),
        'target_environment_id' => (string) $this->targetEnvironment->getKey(),
        'target_key_version' => 1,
        'include_values' => true,
        'entries' => [
            ($this->buildEntry)(true),
        ],
    ]);

    $createResponse
        ->assertCreated()
        ->assertJsonPath('data.type', 'environment-variable-promotion-requests')
        ->assertJsonPath('data.attributes.status', EnvironmentVariablePromotionRequestStatus::Pending->value)
        ->assertJsonPath('data.attributes.source_environment_id', (string) $this->sourceEnvironment->getKey())
        ->assertJsonPath('data.attributes.target_environment_id', (string) $this->targetEnvironment->getKey())
        ->assertJsonPath('data.attributes.entry_count', 1)
        ->assertJsonPath('meta.code', 'PROMOTION_REQUIRES_APPROVAL');

    $promotionRequestId = (string) $createResponse->json('data.id');

    $this->getJson("{$this->baseEndpoint}/promotion-requests?status=pending")
        ->assertOk()
        ->assertJsonPath('data.0.id', $promotionRequestId)
        ->assertJsonPath('data.0.attributes.status', EnvironmentVariablePromotionRequestStatus::Pending->value);

    $this->getJson("{$this->baseEndpoint}/promotion-requests/{$promotionRequestId}")
        ->assertOk()
        ->assertJsonPath('data.id', $promotionRequestId)
        ->assertJsonPath('data.attributes.entry_count', 1);

    $this->postJson("{$this->baseEndpoint}/promotion-requests/{$promotionRequestId}/cancel", [
        'reason' => 'No longer needed.',
    ])
        ->assertOk()
        ->assertJsonPath('data.id', $promotionRequestId)
        ->assertJsonPath('data.attributes.status', EnvironmentVariablePromotionRequestStatus::Cancelled->value)
        ->assertJsonPath('data.attributes.cancel_reason', 'No longer needed.');

    expect(EnvironmentVariablePromotionRequest::query()->find($promotionRequestId)?->status)
        ->toBe(EnvironmentVariablePromotionRequestStatus::Cancelled);
});

test('idempotency key returns the existing pending promotion request on retry', function (): void {
    Sanctum::actingAs($this->user);

    $headers = ['X-Idempotency-Key' => 'promotion-idempotency-001'];
    $requestBody = [
        'device_id' => (string) $this->device->getKey(),
        'target_environment_id' => (string) $this->targetEnvironment->getKey(),
        'target_key_version' => 1,
        'include_values' => true,
        'entries' => [
            ($this->buildEntry)(true),
        ],
    ];

    $first = $this->withHeaders($headers)->postJson("{$this->baseEndpoint}/promotion-requests", $requestBody);
    $first->assertCreated();

    $second = $this->withHeaders($headers)->postJson("{$this->baseEndpoint}/promotion-requests", $requestBody);
    $second
        ->assertOk()
        ->assertJsonPath('data.id', (string) $first->json('data.id'))
        ->assertJsonPath('meta.code', 'PROMOTION_REQUIRES_APPROVAL');
});

test('promotion request rejects include-values when source value is unavailable', function (): void {
    Sanctum::actingAs($this->user);

    $this->postJson("{$this->baseEndpoint}/promotion-requests", [
        'device_id' => (string) $this->device->getKey(),
        'target_environment_id' => (string) $this->targetEnvironment->getKey(),
        'target_key_version' => 1,
        'include_values' => true,
        'entries' => [
            ($this->buildEntry)(false),
        ],
    ])
        ->assertStatus(422)
        ->assertJsonPath('error.code', 'PROMOTION_VALUES_REQUIRED');
});
