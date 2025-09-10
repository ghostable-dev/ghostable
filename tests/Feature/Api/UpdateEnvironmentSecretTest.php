<?php

use App\Environment\Enums\EnvironmentType;
use App\Secret\Actions\CreateSecret;
use App\Secret\Enums\SecretType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

test('updates an environment secret and returns json structure', function () {
    $user = $this->createUser(name: 'Egon', email: 'egon@ghostbusters.com');
    $organization = $this->createOrganization(name: 'Ghostbusters', owner: $user);
    $project = $this->createProject(name: 'Proton Pack', organization: $organization);
    $environment = $this->createEnvironment(
        name: 'staging',
        type: EnvironmentType::STAGING,
        project: $project,
    );

    Sanctum::actingAs($user);

    $secret = app(CreateSecret::class)->handle(
        environment: $environment,
        name: 'API_KEY',
        type: SecretType::TOKEN,
        value: 'initial',
        metadata: ['foo' => 'bar'],
        createdBy: $user,
    );

    $payload = [
        'name' => 'API_KEY',
        'value' => 'updated',
        'type' => SecretType::TOKEN->value,
        'metadata' => ['foo' => 'baz'],
    ];

    $this->putJson("/api/v1/projects/{$project->id}/environments/{$environment->name}/secrets/{$secret->id}", $payload)
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'type',
                'value',
                'created_at',
                'updated_at',
            ],
        ])
        ->assertJsonPath('data.name', 'API_KEY')
        ->assertJsonPath('data.type', SecretType::TOKEN->value);

    $secret->refresh();
    expect($secret->value)->toBe('updated');
    expect($secret->metadata)->toBe(['foo' => 'baz']);
});

test('updating an environment secret logs activity', function () {
    $user = $this->createUser(name: 'Egon', email: 'egon@ghostbusters.com');
    $organization = $this->createOrganization(name: 'Ghostbusters', owner: $user);
    $project = $this->createProject(name: 'Proton Pack', organization: $organization);
    $environment = $this->createEnvironment(
        name: 'staging',
        type: EnvironmentType::STAGING,
        project: $project,
    );

    Sanctum::actingAs($user);

    $secret = app(CreateSecret::class)->handle(
        environment: $environment,
        name: 'API_KEY',
        type: SecretType::TOKEN,
        value: 'initial',
        metadata: ['foo' => 'bar'],
        createdBy: $user,
    );

    $payload = [
        'name' => 'API_KEY',
        'value' => 'updated',
        'type' => SecretType::TOKEN->value,
        'metadata' => ['foo' => 'baz'],
    ];

    $this->putJson("/api/v1/projects/{$project->id}/environments/{$environment->name}/secrets/{$secret->id}", $payload)
        ->assertOk();

    $activity = Activity::query()
        ->where('log_name', 'secret')
        ->where('subject_id', $secret->id)
        ->where('event', 'updated')
        ->first();

    expect($activity)->not->toBeNull();
    expect($activity->causer_id)->toBe($user->id);
    expect($activity->properties['environment_id'])->toBe($environment->id);
});
