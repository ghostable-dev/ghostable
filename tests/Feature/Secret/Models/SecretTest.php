<?php

use App\Environment\Enums\EnvironmentType;
use App\Secret\Actions\CreateSecret;
use App\Secret\Enums\SecretType;
use App\Secret\Models\Secret;
use Illuminate\Contracts\Encryption\Encrypter as EncrypterContract;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['app.key' => 'base64:' . base64_encode(random_bytes(32))]);
    $this->user = $this->createUser('Egon', 'egon@example.com');
    $this->organization = $this->createOrganization('Ghostbusters', $this->user);
    $this->project = $this->createProject('Website', $this->organization);
    $this->environment = $this->createEnvironment('Development', EnvironmentType::DEVELOPMENT, $this->project);
});

test('latest version returns most recent version', function () {
    $secret = app(CreateSecret::class)->handle(
        environment: $this->environment,
        name: 'API_KEY',
        type: SecretType::TOKEN,
        value: 'one',
        metadata: null,
        createdBy: $this->user,
    );

    $secret->value = 'two';
    $secret->save();
    $secret->createVersionBy($this->user);

    expect($secret->latestVersion->version)->toBe(2);
});

test('encrypter falls back to environment when dek missing', function () {
    $secret = new Secret(['environment_id' => $this->environment->id]);
    $encrypter = $secret->encrypter();
    $envEncrypter = $this->environment->encrypter();

    $cipher = $envEncrypter->encryptString('foo');
    expect($encrypter->decryptString($cipher))->toBe('foo');
});

test('rotateDek re-encrypts secret and versions', function () {
    $secret = app(CreateSecret::class)->handle(
        environment: $this->environment,
        name: 'API_KEY',
        type: SecretType::TOKEN,
        value: 'plain',
        metadata: null,
        createdBy: $this->user,
    );

    $originalDek = $secret->dek_wrapped;
    $secret->rotateDek();

    expect($secret->dek_wrapped)->not->toBe($originalDek)
        ->and($secret->encrypter()->decryptString($secret->getRawOriginal('value')))->toBe('plain');

    $version = $secret->versions()->first();
    expect($secret->encrypter()->decryptString($version->getRawOriginal('value')))->toBe('plain');
});
