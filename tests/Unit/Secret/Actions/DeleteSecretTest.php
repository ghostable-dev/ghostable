<?php

use App\Environment\Enums\EnvironmentType;
use App\Secret\Actions\CreateSecret;
use App\Secret\Actions\DeleteSecret;
use App\Secret\Actions\LogSecretActivity;
use App\Secret\Enums\SecretType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery as Mock;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('deletes secret and logs activity', function () {
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

    $logger = Mock::mock(LogSecretActivity::class);
    $logger->shouldReceive('handle')->once()->withArgs(function ($s, $event, $actor) use ($secret, $user) {
        return $s->is($secret) && $event === 'deleted' && $actor->is($user);
    });
    app()->instance(LogSecretActivity::class, $logger);

    app(DeleteSecret::class)->handle(secret: $secret, deletedBy: $user);

    $secret->refresh();
    expect($secret->deleted_at)->not->toBeNull();
    expect($secret->last_updated_by)->toBe($user->id);
    expect($secret->versions)->toHaveCount(2);
});
