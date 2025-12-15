<?php

declare(strict_types=1);

use App\Crypto\Models\Device;
use App\Environment\Enums\EnvironmentType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = $this->createUser('Dana', 'dana@ghostbusters.com');
    $this->organization = $this->createOrganization('Ghostbusters', $this->user);
    $this->project = $this->createProject('Containment Unit', $this->organization);
    $this->environment = $this->createEnvironment('production', EnvironmentType::PRODUCTION, $this->project);

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

    $this->endpoint = sprintf(
        '/api/v2/projects/%s/environments/%s/backups',
        $this->project->getKey(),
        $this->environment->name
    );
});

test('authorized users can create a sealed environment backup and emit audit logs', function (): void {
    Sanctum::actingAs($this->user);

    // Device with signing + encryption keys
    $signingKeypair = sodium_crypto_sign_keypair();
    $secretSigningKey = sodium_crypto_sign_secretkey($signingKeypair);
    $publicSigningKey = sodium_crypto_sign_publickey($signingKeypair);

    $device = Device::factory()->for($this->user)->create([
        'public_key' => base64_encode(random_bytes(SODIUM_CRYPTO_KX_PUBLICKEYBYTES)),
        'public_signing_key' => base64_encode($publicSigningKey),
        'active' => true,
        'revoked_at' => null,
        'name' => 'Proton Pack',
    ]);

    // Ensure the environment has an active key envelope so the bundle includes recipients.
    $this->createEnvironmentKeyWithEnvelope($this->environment, $device);

    $recoveryKeypair = sodium_crypto_kx_keypair();
    $recoveryPublicKey = sodium_crypto_kx_publickey($recoveryKeypair);

    $payload = [
        'device_id' => (string) $device->getKey(),
        'recovery_public_key' => base64_encode($recoveryPublicKey),
        'recovery_label' => 'Org Recovery',
    ];

    $payload = ($this->signPayload)($payload, $secretSigningKey);

    $response = $this->postJson($this->endpoint, $payload);

    $response->assertCreated()
        ->assertJsonPath('backup_id', fn ($id) => is_string($id) && $id !== '')
        ->assertJsonPath('recipients.0.type', 'device')
        ->assertJsonPath('recipients.0.id', (string) $device->getKey())
        ->assertJsonPath('recipients.1.type', 'recovery')
        ->assertJsonPath('statistics.recipient_count', 2)
        ->assertJsonPath('payload.alg', 'xchacha20-poly1305')
        ->assertJsonPath('payload.nonce_b64', fn ($v) => is_string($v) && $v !== '')
        ->assertJsonPath('payload.ciphertext_b64', fn ($v) => is_string($v) && $v !== '')
        ->assertJsonPath('payload.aad_b64', fn ($v) => is_string($v) && $v !== '')
        ->assertJsonPath('environment.name', $this->environment->name);

    $activity = Activity::query()
        ->where('log_name', 'backup')
        ->where('event', 'created')
        ->where('subject_id', $this->environment->id)
        ->latest()
        ->first();

    expect($activity)->not->toBeNull();
    expect(data_get($activity->properties, 'requested_by.email'))->toBe($this->user->email);
    expect(data_get($activity->properties, 'backup.backup_id'))->toBe($response->json('backup_id'));
    expect(data_get($activity->properties, 'recipients.0.type'))->toBe('device');
});

test('invalid signatures are rejected when creating a backup', function (): void {
    Sanctum::actingAs($this->user);

    $device = Device::factory()->for($this->user)->create([
        'public_key' => base64_encode(random_bytes(SODIUM_CRYPTO_KX_PUBLICKEYBYTES)),
        'public_signing_key' => base64_encode(random_bytes(SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES)),
        'active' => true,
        'revoked_at' => null,
    ]);

    $this->createEnvironmentKeyWithEnvelope($this->environment, $device);

    $payload = [
        'device_id' => (string) $device->getKey(),
        'client_sig' => base64_encode(str_repeat('x', 32)),
    ];

    $this->postJson($this->endpoint, $payload)
        ->assertStatus(422)
        ->assertJsonPath('error.fields.client_sig.0', 'Invalid signature format.');
});
