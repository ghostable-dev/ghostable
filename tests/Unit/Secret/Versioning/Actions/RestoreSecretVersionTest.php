<?php

use App\Environment\Enums\EnvironmentType;
use App\Secret\Actions\CreateSecret;
use App\Secret\Actions\UpdateSecret;
use App\Secret\Actions\LogSecretActivity;
use App\Secret\Enums\SecretType;
use App\Secret\Versioning\Actions\RestoreSecretVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery as Mock;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('restores secret version and logs activity', function () {
    $user = $this->createUser('Ray', 'ray@example.com');
    $org = $this->createOrganization('Org', $user);
    $project = $this->createProject('Proj', $org);
    $env = $this->createEnvironment('Env', EnvironmentType::DEVELOPMENT, $project);

    $secret = app(CreateSecret::class)->handle(
        environment: $env,
        name: 'API_KEY',
        type: SecretType::GENERIC,
        value: 'foo',
        metadata: ['orig' => 'value'],
        createdBy: $user,
    );

    app(UpdateSecret::class)->handle(
        secret: $secret,
        name: 'API_KEY_NEW',
        type: SecretType::TOKEN,
        value: 'bar',
        metadata: ['new' => 'val'],
        updatedBy: $user,
    );

    $version = $secret->versions()->where('version', 1)->first();

    $logger = Mock::mock(LogSecretActivity::class);
    $logger->shouldReceive('handle')->once()->withArgs(function ($s, $event, $actor) use ($secret, $user) {
        return $s->is($secret) && $event === 'restored' && $actor->is($user);
    });
    app()->instance(LogSecretActivity::class, $logger);

    app(RestoreSecretVersion::class)->handle(
        version: $version,
        restoredBy: $user,
    );

    $secret->refresh();

    expect($secret->name)->toBe('API_KEY')
        ->and($secret->type)->toBe(SecretType::GENERIC)
        ->and($secret->value)->toBe('foo')
        ->and($secret->metadata)->toMatchArray(['orig' => 'value'])
        ->and($secret->last_updated_by)->toBe($user->id)
        ->and($secret->versions)->toHaveCount(3);

    $latest = $secret->latestVersion;

    expect($latest->version)->toBe(3)
        ->and($latest->name)->toBe('API_KEY')
        ->and($latest->type)->toBe(SecretType::GENERIC)
        ->and($latest->value)->toBe('foo')
        ->and($latest->metadata)->toMatchArray(['orig' => 'value'])
        ->and($latest->changed_by)->toBe($user->id);
});
