<?php

use App\Environment\Enums\EnvironmentType;
use App\Secret\Actions\CreateSecret;
use App\Secret\Actions\RotateSecretDek;
use App\Secret\Enums\SecretType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('rotates secret encryption key and rewrites versions', function () {
    $user = $this->createUser('Ray', 'ray@example.com');
    $org = $this->createOrganization('Org', $user);
    $project = $this->createProject('Proj', $org);
    $env = $this->createEnvironment('Env', EnvironmentType::DEVELOPMENT, $project);

    $secret = app(CreateSecret::class)->handle(
        environment: $env,
        name: 'API_KEY',
        type: SecretType::GENERIC,
        value: 'foo',
        metadata: null,
        createdBy: $user,
    );

    // create another version to ensure all versions are rewrapped
    $secret->createVersionBy($user);

    $originalDek = $secret->dek_wrapped;
    $originalValues = $secret->versions->pluck('value');

    app(RotateSecretDek::class)->handle($secret);

    $secret->refresh();

    expect($secret->dek_wrapped)->not->toBe($originalDek);
    expect($secret->value)->toBe('foo');
    expect($secret->versions->pluck('value'))->toEqual($originalValues);
});
