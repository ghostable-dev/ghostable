<?php

use App\Crypto\Models\Device;
use App\Environment\Enums\EnvironmentType;
use App\Environment\Models\EnvironmentSecret;
use Laravel\Sanctum\Sanctum;
use Spatie\Activitylog\Models\Activity;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function (): void {
    $this->owner = $this->createUser('Dana Barrett', 'dana@example.com');
    $this->collaborator = $this->createUser('Peter Venkman', 'peter@example.com');
    $this->organization = $this->createOrganization('Ghostbusters HQ', $this->owner, [$this->collaborator]);
    $this->project = $this->createProject('Containment Unit', $this->organization);
    $this->environment = $this->createEnvironment('production', EnvironmentType::PRODUCTION, $this->project);

    $this->pushEndpoint = "/api/v2/projects/{$this->project->id}/environments/{$this->environment->name}/push";
    $this->pullEndpoint = "/api/v2/projects/{$this->project->id}/environments/{$this->environment->name}/pull";

    $this->deviceSigningKeypair = sodium_crypto_sign_keypair();
    $this->deviceSigningSecretKey = sodium_crypto_sign_secretkey($this->deviceSigningKeypair);
    $this->deviceSigningPublicKey = sodium_crypto_sign_publickey($this->deviceSigningKeypair);

    $this->device = Device::factory()->for($this->owner)->create([
        'public_key' => base64_encode(random_bytes(32)),
        'public_signing_key' => base64_encode($this->deviceSigningPublicKey),
        'platform' => 'macos',
        'client_type' => 'cli',
    ]);

    $this->collaboratorDevice = Device::factory()->for($this->collaborator)->create([
        'public_key' => base64_encode(random_bytes(32)),
        'public_signing_key' => base64_encode(random_bytes(32)),
        'platform' => 'macos',
        'client_type' => 'cli',
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

test('users on different accounts can push and pull shared environment secrets via v2', function (): void {
    Sanctum::actingAs($this->owner);

    $plaintext = 'ThereIsNoDanaOnlyZuul';
    $secretPayload = ($this->signPayload)(
        ($this->makeSecretPayload)('DB_PASSWORD', $plaintext)
    );
    $expectedHmac = data_get($secretPayload, 'claims.hmac');

    $this->postJson($this->pushEndpoint, [
        'device_id' => (string) $this->device->id,
        'secrets' => [$secretPayload],
    ])
        ->assertOk()
        ->assertJsonPath('data.added', 1)
        ->assertJsonPath('data.updated', 0)
        ->assertJsonPath('data.removed', 0);

    /** @var EnvironmentSecret|null $stored */
    $stored = EnvironmentSecret::query()
        ->where('environment_id', $this->environment->id)
        ->where('name', 'DB_PASSWORD')
        ->first();

    expect($stored)->not->toBeNull();
    expect($stored->version)->toBe(1);
    expect($stored->last_updated_by)->toBe($this->owner->id);
    expect(data_get($stored->claims, 'hmac'))->toBe($expectedHmac);

    Sanctum::actingAs($this->collaborator);

    $response = $this->getJson("{$this->pullEndpoint}?".http_build_query([
        'include_meta' => 1,
        'include_versions' => 1,
        'device_id' => (string) $this->collaboratorDevice->id,
    ]));

    $response->assertOk()
        ->assertJsonPath('env', $this->environment->name)
        ->assertJsonCount(1, 'secrets')
        ->assertJsonPath('secrets.0.name', 'DB_PASSWORD')
        ->assertJsonPath('secrets.0.version', 1)
        ->assertJsonPath('secrets.0.updated_by', $this->owner->email)
        ->assertJsonPath('secrets.0.ciphertext', $secretPayload['ciphertext'])
        ->assertJsonPath('secrets.0.nonce', $secretPayload['nonce'])
        ->assertJsonPath('secrets.0.meta.line_bytes', strlen($plaintext))
        ->assertJsonPath('secrets.0.meta.is_vapor_secret', false)
        ->assertJsonPath('secrets.0.meta.is_commented', false);

    $pushActivity = Activity::query()
        ->where('subject_type', $this->environment->getMorphClass())
        ->where('subject_id', $this->environment->id)
        ->where('event', 'push')
        ->where('causer_id', $this->owner->id)
        ->first();

    expect($pushActivity)->not->toBeNull();
    expect($pushActivity->description)->toBe('Pushed "production" environment via cli.');
    expect(data_get($pushActivity->properties, 'result.added'))->toBe(1);
    expect(data_get($pushActivity->properties, 'result.updated'))->toBe(0);
    expect(data_get($pushActivity->properties, 'result.removed'))->toBe(0);
    expect(data_get($pushActivity->properties, 'environment.name'))->toBe('production');
    expect(data_get($pushActivity->properties, 'device.id'))->toBe((string) $this->device->id);
    expect(data_get($pushActivity->properties, 'ip_address'))->toBe('127.0.0.1');

    $downloadActivity = Activity::query()
        ->where('subject_type', $this->environment->getMorphClass())
        ->where('subject_id', $this->environment->id)
        ->where('event', 'pulled')
        ->where('causer_id', $this->collaborator->id)
        ->first();

    expect($downloadActivity)->not->toBeNull();
    expect($downloadActivity->description)->toBe("Pulled 'production' environment via cli.");
    expect(data_get($downloadActivity->properties, 'source'))->toBe('cli');
    expect(data_get($downloadActivity->properties, 'filters.include_meta'))->toBeTrue();
    expect(data_get($downloadActivity->properties, 'filters.include_versions'))->toBeTrue();
    expect(data_get($downloadActivity->properties, 'secrets_returned'))->toBe(1);
    expect(data_get($downloadActivity->properties, 'environment.name'))->toBe('production');
    expect(data_get($downloadActivity->properties, 'device.id'))->toBe((string) $this->collaboratorDevice->id);
    expect(data_get($downloadActivity->properties, 'ip_address'))->toBe('127.0.0.1');
});
