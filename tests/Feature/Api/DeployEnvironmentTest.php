<?php

use App\Environment\Actions\Token\CreateDeploymentToken as CreateDeploymentTokenAction;
use App\Environment\Enums\EnvironmentType;
use App\Environment\Models\DeploymentToken;
use App\Environment\Models\EnvironmentKey;
use App\Environment\Variable\Actions\CreateVariable;
use App\Environment\Variable\Entities\CreateVariableData;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->user = $this->createUser(name: 'Ray', email: 'ray@ghostbusters.com');
    $organization = $this->createOrganization(name: 'Ray\'s Occult Books', owner: $this->user);
    $project = $this->createProject(name: 'Website', organization: $organization);
    $this->environment = $this->createEnvironment(name: 'Production', type: EnvironmentType::PRODUCTION, project: $project);
    $this->endpoint = '/api/v1/ci/deploy';
    app(CreateVariable::class)->handle(new CreateVariableData(
        environment: $this->environment,
        key: 'APP_NAME',
        value: 'Ghostable',
    ));
    app(CreateVariable::class)->handle(new CreateVariableData(
        environment: $this->environment,
        key: 'APP_DEBUG',
        value: 'false',
    ));
    app(CreateVariable::class)->handle(new CreateVariableData(
        environment: $this->environment,
        key: 'APP_URL',
        value: 'https://ghostable.dev',
    ));
    app(CreateVariable::class)->handle(new CreateVariableData(
        environment: $this->environment,
        key: 'APP_ENV',
        value: 'production',
    ));
    app(CreateVariable::class)->handle(new CreateVariableData(
        environment: $this->environment,
        key: 'APP_KEY',
        value: 'base64:dTk5YXdvZ3V5bDdrZTRnNm1sbHk2YzI1NW0zcnNmcWg=',
    ));
});

test('unauthenticated users cannot deploy environments', function () {
    $this->get($this->endpoint)->assertUnauthorized();
});

test('environment token can deploy environment', function () {
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

    /** @var \App\Environment\Actions\Token\DeploymentTokenResult $tokenResult */
    $tokenResult = app(CreateDeploymentTokenAction::class)->handle(
        name: 'deploy',
        environment: $this->environment,
        publicKey: base64_encode(random_bytes(32)),
        user: $this->user,
    );

    /** @var DeploymentToken $deploymentToken */
    $deploymentToken = $tokenResult->token;

    $response = $this->withHeaders(['Authorization' => 'Bearer '.$tokenResult->plainTextSecret])
        ->getJson($this->endpoint);

    $response->assertOk()
        ->assertJsonPath('environment_key.data.attributes.fingerprint', $environmentKey->fingerprint)
        ->assertJsonPath(
            'environment_key.data.relationships.envelope.data.attributes.recipients.0.type',
            'deployment'
        )
        ->assertJsonPath(
            'environment_key.data.relationships.envelope.data.attributes.recipients.0.id',
            (string) $deploymentToken->getKey()
        )
        ->assertJsonMissingPath('environmentKey');

    $envelopeAttributes = $response->json('environment_key.data.relationships.envelope.data.attributes');

    expect($envelopeAttributes['from_ephemeral_public_key'] ?? null)
        ->toBeString()
        ->toStartWith('b64:');

    $recipientPayloadB64 = $envelopeAttributes['recipients'][0]['edek_b64'] ?? null;
    expect($recipientPayloadB64)
        ->toBeString()
        ->toStartWith('b64:');

    $decodedPayload = base64_decode(substr($recipientPayloadB64, 4), true);

    expect($decodedPayload)->toBeString();

    try {
        $payload = json_decode($decodedPayload, true, 512, JSON_THROW_ON_ERROR);
    } catch (\JsonException $exception) {
        $this->fail('Failed to decode deployment recipient payload: '.$exception->getMessage());
    }

    expect($payload)->toBeArray()
        ->toHaveKeys(['ciphertext_b64', 'nonce_b64', 'alg', 'aad_b64', 'from_ephemeral_public_key']);

    expect($payload['from_ephemeral_public_key'] ?? null)
        ->toBe($envelopeAttributes['from_ephemeral_public_key']);
});

test('user tokens cannot deploy environments', function () {
    $userToken = $this->user->createToken('test');

    $this->withHeaders(['Authorization' => 'Bearer '.$userToken->plainTextToken])
        ->get($this->endpoint)
        ->assertForbidden();
});
