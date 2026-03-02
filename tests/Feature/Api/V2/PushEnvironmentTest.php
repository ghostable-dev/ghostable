<?php

use App\Core\Models\Activity;
use App\Crypto\Models\Device;
use App\Environment\Enums\EnvironmentType;
use Laravel\Sanctum\Sanctum;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->ray = $this->createUser(name: 'Ray', email: 'ray@ghostbusters.com');
    $this->organization = $this->createOrganization(name: 'Ray’s Occult Books', owner: $this->ray);
    $project = $this->createProject(name: 'Website', organization: $this->organization);
    $this->env = $this->createEnvironment(name: 'Website', type: EnvironmentType::DEVELOPMENT, project: $project);
    $this->endpoint = "/api/v2/projects/{$project->id}/environments/{$this->env->name}/push";

    $deviceKeypair = sodium_crypto_sign_keypair();
    $this->deviceSecretKey = sodium_crypto_sign_secretkey($deviceKeypair);
    $devicePublicKey = sodium_crypto_sign_publickey($deviceKeypair);

    $this->device = Device::factory()->for($this->ray)->create([
        'active' => true,
        'revoked_at' => null,
        'public_signing_key' => base64_encode($devicePublicKey),
    ]);

    $this->signSecretPayload = function (array $payload): array {
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

    $this->makeSecretPayload = function (string $name, array $overrides = []): array {
        $base = [
            'name' => $name,
            'ciphertext' => "ciphertext-{$name}-v1",
            'nonce' => "nonce-{$name}-v1",
            'alg' => 'xchacha20-poly1305',
            'aad' => [
                'org' => (string) $this->organization->id,
                'project' => (string) $this->env->project->id,
                'env' => (string) $this->env->id,
                'name' => $name,
            ],
            'claims' => [
                'hmac' => "hmac-{$name}-v1",
                'meta' => [
                    'value_length' => 12,
                    'is_vapor_secret' => false,
                    'is_commented' => false,
                    'is_override' => false,
                ],
            ],
            'client_sig' => "sig-{$name}-v1",
        ];

        $payload = array_replace_recursive($base, $overrides);

        return ($this->signSecretPayload)($payload);
    };
});

test('push does not remove secrets by default', function () {
    Sanctum::actingAs($this->ray);

    $initial = [
        ($this->makeSecretPayload)('APP_DEBUG'),
        ($this->makeSecretPayload)('APP_ENV'),
        ($this->makeSecretPayload)('APP_KEY'),
        ($this->makeSecretPayload)('APP_URL'),
        ($this->makeSecretPayload)('CACHE_DRIVER'),
    ];

    $this->postJson($this->endpoint, [
        'device_id' => (string) $this->device->getKey(),
        'secrets' => $initial,
    ])->assertOk();

    $second = [
        ($this->makeSecretPayload)('APP_DEBUG', [
            'ciphertext' => 'ciphertext-APP_DEBUG-v2',
            'nonce' => 'nonce-APP_DEBUG-v2',
            'claims' => [
                'hmac' => 'hmac-APP_DEBUG-v2',
            ],
        ]),
        ($this->makeSecretPayload)('APP_ENV'),
        ($this->makeSecretPayload)('APP_KEY'),
        ($this->makeSecretPayload)('APP_URL'),
    ];

    $this->postJson($this->endpoint, [
        'device_id' => (string) $this->device->getKey(),
        'secrets' => $second,
    ])
        ->assertOk()
        ->assertJsonFragment(['removed' => 0]);

    $this->env->refresh();

    expect(
        $this->env->envSecrets()->where('name', 'CACHE_DRIVER')->exists()
    )->toBeTrue();
});

test('push removes secrets when sync is true', function () {
    Sanctum::actingAs($this->ray);

    $initial = [
        ($this->makeSecretPayload)('APP_DEBUG'),
        ($this->makeSecretPayload)('APP_ENV'),
        ($this->makeSecretPayload)('APP_KEY'),
        ($this->makeSecretPayload)('APP_URL'),
        ($this->makeSecretPayload)('CACHE_DRIVER'),
    ];

    $this->postJson($this->endpoint, [
        'device_id' => (string) $this->device->getKey(),
        'secrets' => $initial,
    ])->assertOk();

    $second = [
        ($this->makeSecretPayload)('APP_DEBUG', [
            'ciphertext' => 'ciphertext-APP_DEBUG-v2',
            'nonce' => 'nonce-APP_DEBUG-v2',
            'claims' => [
                'hmac' => 'hmac-APP_DEBUG-v2',
            ],
        ]),
        ($this->makeSecretPayload)('APP_ENV'),
        ($this->makeSecretPayload)('APP_KEY'),
        ($this->makeSecretPayload)('APP_URL'),
    ];

    $this->postJson($this->endpoint, [
        'device_id' => (string) $this->device->getKey(),
        'secrets' => $second,
        'sync' => true,
    ])
        ->assertOk()
        ->assertJsonFragment(['removed' => 1]);

    $this->env->refresh();

    expect(
        $this->env->envSecrets()->where('name', 'CACHE_DRIVER')->exists()
    )->toBeFalse();
});

test('push returns deterministic version conflict payload', function () {
    Sanctum::actingAs($this->ray);

    $this->postJson($this->endpoint, [
        'device_id' => (string) $this->device->getKey(),
        'secrets' => [
            ($this->makeSecretPayload)('APP_KEY'),
        ],
    ])->assertOk();

    $response = $this->postJson($this->endpoint, [
        'device_id' => (string) $this->device->getKey(),
        'secrets' => [
            ($this->makeSecretPayload)('APP_KEY', [
                'if_version' => 0,
            ]),
        ],
    ]);

    $response
        ->assertStatus(409)
        ->assertJsonPath('error.code', 'version_conflict')
        ->assertJsonPath('conflicts.0.key', 'APP_KEY')
        ->assertJsonPath('conflicts.0.server_version', 1)
        ->assertJsonPath('conflicts.0.client_if_version', 0);
});

test('push force_overwrite bypasses stale if_version and logs explicit audit event', function () {
    Sanctum::actingAs($this->ray);

    $this->postJson($this->endpoint, [
        'device_id' => (string) $this->device->getKey(),
        'secrets' => [
            ($this->makeSecretPayload)('APP_KEY'),
        ],
    ])->assertOk();

    $this->postJson($this->endpoint, [
        'device_id' => (string) $this->device->getKey(),
        'force_overwrite' => true,
        'secrets' => [
            ($this->makeSecretPayload)('APP_KEY', [
                'if_version' => 0,
                'ciphertext' => 'ciphertext-APP_KEY-v2',
                'nonce' => 'nonce-APP_KEY-v2',
                'claims' => [
                    'hmac' => 'hmac-APP_KEY-v2',
                ],
            ]),
        ],
    ])->assertOk();

    $this->env->refresh();

    expect(
        $this->env->envSecrets()->where('name', 'APP_KEY')->value('version')
    )->toBe(2);

    expect(
        Activity::query()
            ->where('event', 'push_force_overwrite')
            ->where('subject_id', (string) $this->env->getKey())
            ->exists()
    )->toBeTrue();
});
