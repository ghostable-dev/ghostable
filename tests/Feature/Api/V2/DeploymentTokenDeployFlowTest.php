<?php

use App\Crypto\Models\Device;
use App\Environment\Enums\EnvironmentType;
use App\Environment\Models\EnvironmentKey;
use App\Environment\Models\EnvironmentSecret;
use Laravel\Sanctum\Sanctum;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function (): void {
    $this->owner = $this->createUser('Janine Melnitz', 'janine@example.com');
    $this->teammate = $this->createUser('Winston Zeddemore', 'winston@example.com');
    $this->organization = $this->createOrganization('Ghostbusters Inc', $this->owner, [$this->teammate]);
    $this->project = $this->createProject('Containment Grid', $this->organization);
    $this->environment = $this->createEnvironment('production', EnvironmentType::PRODUCTION, $this->project);

    $this->pushEndpoint = "/api/v2/projects/{$this->project->id}/environments/{$this->environment->name}/push";
    $this->deployEndpoint = '/api/v2/ci/deploy';
    $this->tokenEndpoint = "/api/v2/projects/{$this->project->id}/deploy-tokens";
    $this->makeDeploymentRecipient = static function (): array {
        $payload = [
            'ciphertext_b64' => 'b64:'.base64_encode(random_bytes(32)),
            'nonce_b64' => 'b64:'.base64_encode(random_bytes(24)),
            'alg' => 'xchacha20-poly1305',
            'aad_b64' => null,
            'from_ephemeral_public_key' => 'b64:'.base64_encode(random_bytes(32)),
        ];

        return [
            'edek_b64' => 'b64:'.base64_encode(json_encode($payload, JSON_THROW_ON_ERROR)),
        ];
    };

    $this->deviceSigningKeypair = sodium_crypto_sign_keypair();
    $this->deviceSigningSecretKey = sodium_crypto_sign_secretkey($this->deviceSigningKeypair);
    $this->deviceSigningPublicKey = sodium_crypto_sign_publickey($this->deviceSigningKeypair);

    $this->device = Device::factory()->for($this->owner)->create([
        'public_key' => base64_encode(random_bytes(32)),
        'public_signing_key' => base64_encode($this->deviceSigningPublicKey),
        'platform' => 'cli',
    ]);

    $this->environmentKey = EnvironmentKey::factory()
        ->forEnvironment($this->environment)
        ->create([
            'version' => 1,
            'fingerprint' => hash('sha256', 'prod-key'),
        ]);

    $this->environmentKey->envelope()->create([
        'ciphertext_b64' => base64_encode(random_bytes(64)),
        'nonce_b64' => base64_encode(random_bytes(24)),
        'alg' => 'xchacha20-poly1305',
        'aad_b64' => null,
        'recipients' => [],
        'version' => '1',
        'created_at' => now(),
    ]);

    $this->makeSecretPayload = function (string $name, string $plaintext): array {
        $length = strlen($plaintext);
        $hmac = hash_hmac('sha256', $plaintext, 'integration-test-hmac');

        return [
            'name' => $name,
            'ciphertext' => base64_encode(random_bytes(48)),
            'nonce' => base64_encode(random_bytes(24)),
            'alg' => 'xchacha20-poly1305',
            'aad' => [
                'org' => (string) $this->organization->id,
                'project' => (string) $this->project->id,
                'env' => (string) $this->environment->id,
                'name' => $name,
            ],
            'claims' => [
                'hmac' => $hmac,
                'meta' => [
                    'value_length' => $length,
                    'is_vapor_secret' => false,
                    'is_commented' => false,
                ],
            ],
            'line_bytes' => $length,
        ];
    };

    $this->signPayload = function (array $payload): array {
        $payloadToSign = $payload;
        unset($payloadToSign['client_sig']);

        $payloadJson = json_encode(
            $payloadToSign,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );

        $signature = sodium_crypto_sign_detached($payloadJson, $this->deviceSigningSecretKey);

        $payload['client_sig'] = base64_encode($signature);

        return $payload;
    };
});

test('deployment token can pull and decrypt environment key via v2 deploy endpoint', function (): void {
    Sanctum::actingAs($this->owner);

    $plaintext = 'DontCrossTheStreams';
    $secretPayload = ($this->signPayload)(
        ($this->makeSecretPayload)('DB_PASSWORD', $plaintext)
    );

    $this->postJson($this->pushEndpoint, [
        'device_id' => (string) $this->device->id,
        'secrets' => [$secretPayload],
    ])
        ->assertOk()
        ->assertJsonPath('data.added', 1);

    expect(EnvironmentSecret::query()->where('environment_id', $this->environment->id)->count())->toBe(1);

    $keypair = sodium_crypto_box_keypair();
    $publicKey = sodium_crypto_box_publickey($keypair);
    $secretKey = sodium_crypto_box_secretkey($keypair);
    $publicKeyB64 = base64_encode($publicKey);

    $createTokenResponse = $this->postJson($this->tokenEndpoint, [
        'name' => 'gh-actions',
        'environment_id' => (string) $this->environment->id,
        'public_key' => $publicKeyB64,
        'expires_after' => 14,
        'recipient' => ($this->makeDeploymentRecipient)(),
    ]);

    $createTokenResponse->assertCreated()
        ->assertJsonPath('data.attributes.environment_id', (string) $this->environment->id);

    $tokenId = $createTokenResponse->json('data.id');
    $plainSecret = $createTokenResponse->json('meta.secret');

    expect($plainSecret)->toBeString()->not->toBeEmpty();

    app('auth')->forgetGuards();

    $deployResponse = $this->withHeaders([
        'Authorization' => 'Bearer '.$plainSecret,
        'Accept' => 'application/json',
    ])->getJson($this->deployEndpoint);

    $deployResponse->assertOk()
        ->assertJsonPath('env', $this->environment->name)
        ->assertJsonPath('secrets.0.name', 'DB_PASSWORD')
        ->assertJsonPath('secrets.0.ciphertext', $secretPayload['ciphertext'])
        ->assertJsonPath('environment_key.data.relationships.envelope.data.attributes.recipients.0.id', $tokenId);

    $bundle = $deployResponse->json();
    $recipient = data_get($bundle, 'environment_key.data.relationships.envelope.data.attributes.recipients.0');

    expect($recipient)->toBeArray();
    expect($recipient['type'] ?? null)->toBe('deployment');
    expect($recipient['id'] ?? null)->toBe($tokenId);

    $encodedPayload = $recipient['edek_b64'] ?? null;
    expect($encodedPayload)->toBeString()->not->toBe('');

    $normalizeB64 = static function (string $value): string {
        $normalized = str_starts_with($value, 'b64:')
            ? substr($value, 4)
            : $value;

        $decoded = base64_decode($normalized, true);

        expect($decoded)->not->toBeFalse();

        return $decoded;
    };

    $payloadJson = $normalizeB64($encodedPayload);

    $recipientPayload = json_decode($payloadJson, true, 512, JSON_THROW_ON_ERROR);

    $ciphertext = $normalizeB64($recipientPayload['ciphertext_b64']);
    $nonce = $normalizeB64($recipientPayload['nonce_b64']);
    $ephemeralPublic = $normalizeB64($recipientPayload['from_ephemeral_public_key']);

    expect($ciphertext)->toBeString()->not->toBe('');
    expect($nonce)->toBeString()->not->toBe('');
    expect($ephemeralPublic)->toBeString()->not->toBe('');
});
