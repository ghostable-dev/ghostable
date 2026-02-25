<?php

declare(strict_types=1);

use App\Environment\Actions\Token\CreateDeploymentToken as CreateDeploymentTokenAction;
use App\Environment\Actions\Token\DeploymentTokenResult;
use App\Environment\Enums\EnvironmentType;
use App\Environment\Models\DeploymentToken;
use App\Environment\Models\EnvironmentKey;
use Laravel\Sanctum\Sanctum;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = $this->createUser('Dana', 'dana@ghostbusters.com');
    $this->member = $this->createUser('Winston', 'winston@ghostbusters.com');
    $this->organization = $this->createOrganization('Ghostbusters', $this->user, [$this->member]);
    $this->project = $this->createProject('Containment Unit', $this->organization);
    $this->environment = $this->createEnvironment('production', EnvironmentType::PRODUCTION, $this->project);
    $this->endpoint = sprintf('/api/v2/projects/%s/deploy-tokens', $this->project->getKey());

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
});

test('can create a deployment token', function (): void {
    Sanctum::actingAs($this->user);

    $environmentKey = EnvironmentKey::factory()
        ->forEnvironment($this->environment)
        ->create([
            'fingerprint' => hash('sha256', 'production-key'),
        ]);

    $environmentKey->envelope()->create([
        'ciphertext_b64' => base64_encode(random_bytes(64)),
        'nonce_b64' => base64_encode(random_bytes(24)),
        'alg' => 'xchacha20-poly1305',
        'version' => '1',
        'recipients' => [],
    ]);

    $payload = [
        'name' => 'CI pipeline',
        'environment_id' => (string) $this->environment->getKey(),
        'public_key' => base64_encode(random_bytes(32)),
        'expires_after' => 45,
        'recipient' => ($this->makeDeploymentRecipient)(),
    ];

    $response = $this->postJson($this->endpoint, $payload);

    $response->assertCreated()
        ->assertJsonPath('data.type', 'deployment')
        ->assertJsonPath('data.attributes.environment_id', $payload['environment_id'])
        ->assertJsonPath('data.attributes.name', $payload['name']);

    $secret = $response->json('meta.secret');
    $apiToken = $response->json('meta.api_token');

    expect($secret)->toBeString();
    expect($secret)->toContain('|');
    expect($apiToken)->toBeArray();
    expect($apiToken['plain_text'] ?? null)->toBe($secret);
    expect($apiToken['id'] ?? null)->toBeString();
    expect($apiToken['token_suffix'] ?? null)->toBeString();

    /** @var DeploymentToken $token */
    $token = DeploymentToken::query()->firstOrFail();

    expect($token->public_key)->toBe($payload['public_key']);
    expect($token->token_suffix)->not()->toBeNull();
    expect($token->personal_access_token_id)->not()->toBeNull();

    $this->assertDatabaseHas('deployment_tokens', [
        'id' => $token->getKey(),
        'environment_id' => $this->environment->getKey(),
        'project_id' => $this->project->getKey(),
    ]);

    $environmentKey->refresh();

    $recipients = $environmentKey->envelope?->recipients ?? [];
    $deploymentRecipient = collect($recipients)->first(
        fn ($recipient) => is_array($recipient)
            && ($recipient['type'] ?? '') === 'deployment'
            && ($recipient['id'] ?? '') === (string) $token->getKey()
    );

    expect($deploymentRecipient)->not->toBeNull();
    expect($deploymentRecipient['edek_b64'] ?? null)->toBeString();
    expect($deploymentRecipient['edek_b64'])->toStartWith('b64:');

    $decoded = base64_decode(substr($deploymentRecipient['edek_b64'], 4), true);
    expect($decoded)->toBeString();

    try {
        $recipientPayload = json_decode($decoded, true, 512, JSON_THROW_ON_ERROR);
    } catch (\JsonException $exception) {
        $this->fail('Failed to decode deployment recipient payload: '.$exception->getMessage());
    }

    expect($recipientPayload)->toBeArray()
        ->toHaveKeys(['ciphertext_b64', 'nonce_b64', 'alg', 'aad_b64', 'from_ephemeral_public_key']);
});

test('non administrators cannot create deployment tokens', function (): void {
    Sanctum::actingAs($this->member);

    $payload = [
        'name' => 'Unauthorized token',
        'environment_id' => (string) $this->environment->getKey(),
        'public_key' => base64_encode(random_bytes(32)),
    ];

    $this->postJson($this->endpoint, $payload)->assertForbidden();
});

test('listing deployment tokens returns project tokens', function (): void {
    Sanctum::actingAs($this->user);

    /** @var DeploymentTokenResult $first */
    $first = app(CreateDeploymentTokenAction::class)->handle(
        name: 'Primary token',
        environment: $this->environment,
        publicKey: base64_encode(random_bytes(32)),
        user: $this->user,
        recipient: ($this->makeDeploymentRecipient)(),
    );

    /** @var DeploymentTokenResult $second */
    $secondEnvironment = $this->createEnvironment('staging', EnvironmentType::STAGING, $this->project);

    $second = app(CreateDeploymentTokenAction::class)->handle(
        name: 'Staging token',
        environment: $secondEnvironment,
        publicKey: base64_encode(random_bytes(32)),
        user: $this->user,
        recipient: ($this->makeDeploymentRecipient)(),
    );

    $response = $this->getJson($this->endpoint.'?environment_id='.(string) $this->environment->getKey());

    $response->assertOk()->assertJsonPath('meta.count', 1);

    $ids = collect($response->json('data'))->pluck('id')->all();
    expect($ids)->toContain((string) $first->token->getKey());

    $allResponse = $this->getJson($this->endpoint);
    $allResponse->assertOk()->assertJsonPath('meta.count', 2);

    $allIds = collect($allResponse->json('data'))->pluck('id');
    expect($allIds)->toContain((string) $first->token->getKey());
    expect($allIds)->toContain((string) $second->token->getKey());
});

test('listing deployment tokens accepts environment name filters for legacy clients', function (): void {
    Sanctum::actingAs($this->user);

    /** @var DeploymentTokenResult $primaryToken */
    $primaryToken = app(CreateDeploymentTokenAction::class)->handle(
        name: 'Primary token',
        environment: $this->environment,
        publicKey: base64_encode(random_bytes(32)),
        user: $this->user,
        recipient: ($this->makeDeploymentRecipient)(),
    );

    $secondaryEnvironment = $this->createEnvironment('desktop', EnvironmentType::LOCAL, $this->project);

    app(CreateDeploymentTokenAction::class)->handle(
        name: 'Desktop token',
        environment: $secondaryEnvironment,
        publicKey: base64_encode(random_bytes(32)),
        user: $this->user,
        recipient: ($this->makeDeploymentRecipient)(),
    );

    $response = $this->getJson($this->endpoint.'?environment_id='.$this->environment->name);

    $response->assertOk()->assertJsonPath('meta.count', 1);

    $ids = collect($response->json('data'))->pluck('id')->all();

    expect($ids)->toContain((string) $primaryToken->token->getKey());
});

test('can rotate a deployment token', function (): void {
    Sanctum::actingAs($this->user);

    $environmentKey = EnvironmentKey::factory()
        ->forEnvironment($this->environment)
        ->create([
            'fingerprint' => hash('sha256', 'rotation-key'),
        ]);

    $environmentKey->envelope()->create([
        'ciphertext_b64' => base64_encode(random_bytes(64)),
        'nonce_b64' => base64_encode(random_bytes(24)),
        'alg' => 'xchacha20-poly1305',
        'version' => '1',
        'recipients' => [],
    ]);

    /** @var DeploymentTokenResult $result */
    $result = app(CreateDeploymentTokenAction::class)->handle(
        name: 'Rotating token',
        environment: $this->environment,
        publicKey: base64_encode(random_bytes(32)),
        user: $this->user,
        recipient: ($this->makeDeploymentRecipient)(),
    );

    $token = $result->token->fresh();
    $originalTokenId = $token->personal_access_token_id;

    $environmentKey->refresh();

    $initialRecipient = collect($environmentKey->envelope?->recipients ?? [])
        ->first(fn ($recipient) => is_array($recipient)
            && ($recipient['type'] ?? '') === 'deployment'
            && ($recipient['id'] ?? '') === (string) $token->getKey());

    expect($initialRecipient)->not->toBeNull();
    expect($initialRecipient['edek_b64'] ?? null)->toStartWith('b64:');

    $initialDecoded = base64_decode(substr($initialRecipient['edek_b64'], 4), true);
    expect($initialDecoded)->toBeString();

    try {
        $initialPayload = json_decode($initialDecoded, true, 512, JSON_THROW_ON_ERROR);
    } catch (\JsonException $exception) {
        $this->fail('Failed to decode initial deployment recipient payload: '.$exception->getMessage());
    }

    expect($initialPayload)->toBeArray()
        ->toHaveKeys(['ciphertext_b64', 'nonce_b64', 'alg', 'aad_b64', 'from_ephemeral_public_key']);

    $payload = [
        'public_key' => base64_encode(random_bytes(32)),
        'expires_after' => 60,
        'recipient' => ($this->makeDeploymentRecipient)(),
    ];

    $response = $this->postJson(
        sprintf('%s/%s/rotate', $this->endpoint, $token->getKey()),
        $payload
    );

    $response->assertOk()
        ->assertJsonPath('data.attributes.public_key', $payload['public_key']);

    $rotationSecret = $response->json('meta.secret');
    $rotationApiToken = $response->json('meta.api_token');
    expect($rotationSecret)->toBeString();
    expect($rotationSecret)->toContain('|');
    expect($rotationApiToken)->toBeArray();
    expect($rotationApiToken['plain_text'] ?? null)->toBe($rotationSecret);
    expect($rotationApiToken['id'] ?? null)->toBeString();
    expect($rotationApiToken['token_suffix'] ?? null)->toBeString();

    $token->refresh();

    expect($token->public_key)->toBe($payload['public_key']);
    expect($token->personal_access_token_id)->not()->toBe($originalTokenId);
    expect($token->revoked_at)->toBeNull();

    $this->assertDatabaseMissing('personal_access_tokens', [
        'id' => $originalTokenId,
    ]);

    $environmentKey->refresh();

    $deploymentRecipients = collect($environmentKey->envelope?->recipients ?? [])
        ->filter(fn ($recipient) => is_array($recipient)
            && ($recipient['type'] ?? '') === 'deployment'
            && ($recipient['id'] ?? '') === (string) $token->getKey());

    expect($deploymentRecipients->count())->toBe(1);

    $updatedRecipient = $deploymentRecipients->first();

    expect($updatedRecipient['edek_b64'] ?? null)->toStartWith('b64:');
    expect($updatedRecipient['edek_b64'])->not->toBe($initialRecipient['edek_b64'] ?? null);

    $updatedDecoded = base64_decode(substr($updatedRecipient['edek_b64'], 4), true);
    expect($updatedDecoded)->toBeString();

    try {
        $updatedPayload = json_decode($updatedDecoded, true, 512, JSON_THROW_ON_ERROR);
    } catch (\JsonException $exception) {
        $this->fail('Failed to decode updated deployment recipient payload: '.$exception->getMessage());
    }

    expect($updatedPayload)->toBeArray()
        ->toHaveKeys(['ciphertext_b64', 'nonce_b64', 'alg', 'aad_b64', 'from_ephemeral_public_key']);
});

test('can revoke a deployment token', function (): void {
    Sanctum::actingAs($this->user);

    /** @var DeploymentTokenResult $result */
    $result = app(CreateDeploymentTokenAction::class)->handle(
        name: 'Revocable token',
        environment: $this->environment,
        publicKey: base64_encode(random_bytes(32)),
        user: $this->user,
        recipient: ($this->makeDeploymentRecipient)(),
    );

    $token = $result->token->fresh();
    $patId = $token->personal_access_token_id;

    $response = $this->postJson(sprintf('%s/%s/revoke', $this->endpoint, $token->getKey()));

    $response->assertOk()
        ->assertJsonPath('meta.success', true)
        ->assertJsonPath('data.attributes.status', 'revoked');

    $token->refresh();

    expect($token->revoked_at)->not()->toBeNull();
    expect($token->personal_access_token_id)->toBeNull();

    $this->assertDatabaseMissing('personal_access_tokens', [
        'id' => $patId,
    ]);
});

test('cannot rotate a revoked deployment token', function (): void {
    Sanctum::actingAs($this->user);

    /** @var DeploymentTokenResult $result */
    $result = app(CreateDeploymentTokenAction::class)->handle(
        name: 'Expired token',
        environment: $this->environment,
        publicKey: base64_encode(random_bytes(32)),
        user: $this->user,
        recipient: ($this->makeDeploymentRecipient)(),
    );

    $token = $result->token->fresh();

    $this->postJson(sprintf('%s/%s/revoke', $this->endpoint, $token->getKey()))
        ->assertOk();

    $payload = [
        'public_key' => base64_encode(random_bytes(32)),
        'expires_after' => 30,
    ];

    $this->postJson(sprintf('%s/%s/rotate', $this->endpoint, $token->getKey()), $payload)
        ->assertStatus(422);

    $token->refresh();
    expect($token->personal_access_token_id)->toBeNull();
    expect($token->revoked_at)->not()->toBeNull();
});

test('can delete a revoked deployment token', function (): void {
    Sanctum::actingAs($this->user);

    /** @var DeploymentTokenResult $result */
    $result = app(CreateDeploymentTokenAction::class)->handle(
        name: 'Disposable token',
        environment: $this->environment,
        publicKey: base64_encode(random_bytes(32)),
        user: $this->user,
        recipient: ($this->makeDeploymentRecipient)(),
    );

    $token = $result->token->fresh();

    $this->postJson(sprintf('%s/%s/revoke', $this->endpoint, $token->getKey()))
        ->assertOk();

    $this->deleteJson(sprintf('%s/%s', $this->endpoint, $token->getKey()))
        ->assertOk()
        ->assertJsonPath('meta.success', true)
        ->assertJsonPath('meta.deleted_id', (string) $token->getKey());

    $this->assertDatabaseMissing('deployment_tokens', [
        'id' => (string) $token->getKey(),
    ]);
});

test('cannot delete an active deployment token', function (): void {
    Sanctum::actingAs($this->user);

    /** @var DeploymentTokenResult $result */
    $result = app(CreateDeploymentTokenAction::class)->handle(
        name: 'Still active token',
        environment: $this->environment,
        publicKey: base64_encode(random_bytes(32)),
        user: $this->user,
        recipient: ($this->makeDeploymentRecipient)(),
    );

    $token = $result->token->fresh();

    $this->deleteJson(sprintf('%s/%s', $this->endpoint, $token->getKey()))
        ->assertStatus(422);

    $this->assertDatabaseHas('deployment_tokens', [
        'id' => (string) $token->getKey(),
    ]);
});

test('environment tokens cannot manage other projects tokens', function (): void {
    Sanctum::actingAs($this->user);

    $otherOrganization = $this->createOrganization('EPA', $this->createUser('Walter', 'walter@epa.gov'));
    $otherProject = $this->createProject('Inspection', $otherOrganization);

    $this->getJson(sprintf('/api/v2/projects/%s/deploy-tokens', $otherProject->getKey()))
        ->assertForbidden();
});
