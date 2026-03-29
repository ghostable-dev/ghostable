<?php

use App\Environment\Enums\EnvironmentType;
use App\Environment\Models\EnvironmentSecretVersion;
use App\Environment\Models\EnvironmentVariableVersionChangeNote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = $this->createUser('Winston', 'winston@ghostable.com');
    $this->member = $this->createUser('Janine', 'janine@ghostable.com');
});

test('unauthenticated users cannot view project history', function (): void {
    $org = $this->createOrganization('Ghostbusters', $this->user);
    $project = $this->createProject('Containment Unit', $org);

    $this->getJson("/api/v2/projects/{$project->id}/history")
        ->assertUnauthorized();
});

test('members can view project history with entries', function (): void {
    $org = $this->createOrganization('Ghostbusters', $this->user, [$this->member]);
    $project = $this->createProject('Containment Unit', $org);
    $env = $this->createEnvironment('production', EnvironmentType::PRODUCTION, $project);

    // Seed an environment secret version
    $secret = $env->envSecrets()->create([
        'name' => 'DB_PASSWORD',
        'ciphertext' => 'cipher',
        'nonce' => 'nonce',
        'alg' => 'xchacha20poly1305',
        'aad' => [],
        'claims' => ['hmac' => 'abc'],
        'client_sig' => 'sig',
        'version' => 1,
        'last_updated_at' => now(),
    ]);

    $version = new EnvironmentSecretVersion([
        'name' => $secret->name,
        'version' => 1,
        'ciphertext' => $secret->ciphertext,
        'nonce' => $secret->nonce,
        'alg' => $secret->alg,
        'aad' => $secret->aad,
        'claims' => $secret->claims,
        'client_sig' => $secret->client_sig,
        'is_commented' => false,
        'created_at' => now(),
    ]);
    $version->secret()->associate($secret);
    $version->save();

    Sanctum::actingAs($this->member);
    $this->member->refresh();
    $project->refresh();

    $response = $this->getJson("/api/v2/projects/{$project->id}/history");

    $response->assertOk();

    $data = $response->json('data') ?? [];
    expect(data_get($data, 'project.name'))->toBe($project->name);
    expect(data_get($data, 'entries'))->toBeArray();
    expect(collect(data_get($data, 'entries')))->not()->toBeEmpty();
});

test('non-members cannot view project history', function (): void {
    $org = $this->createOrganization('Ghostbusters', $this->user);
    $project = $this->createProject('Containment Unit', $org);

    Sanctum::actingAs($this->member);

    $this->getJson("/api/v2/projects/{$project->id}/history")
        ->assertForbidden();
});

test('project history includes variable context activity entries from environments', function (): void {
    $org = $this->createOrganization('Ghostbusters', $this->user, [$this->member]);
    $project = $this->createProject('Containment Unit', $org);
    $env = $this->createEnvironment('production', EnvironmentType::PRODUCTION, $project);

    activity('variable')
        ->performedOn($env)
        ->causedBy($this->user)
        ->event('context_note_updated')
        ->withProperties([
            'environment' => [
                'id' => (string) $env->id,
                'name' => $env->name,
                'type' => $env->type->value,
            ],
            'variable' => [
                'id' => 'secret-app-key',
                'name' => 'APP_KEY',
                'version' => 7,
            ],
        ])
        ->log('Updated note for variable "APP_KEY" in "production".');

    Sanctum::actingAs($this->member);

    $this->getJson("/api/v2/projects/{$project->id}/history")
        ->assertOk()
        ->assertJsonPath('data.entries.0.operation', 'note_updated')
        ->assertJsonPath('data.entries.0.variable.name', 'APP_KEY')
        ->assertJsonPath('data.entries.0.scope.environment.name', $env->name)
        ->assertJsonPath('data.entries.0.description', 'Updated note for variable "APP_KEY" in "production".');
});

test('project history includes deleted comment activity entries from environments', function (): void {
    $org = $this->createOrganization('Ghostbusters', $this->user, [$this->member]);
    $project = $this->createProject('Containment Unit', $org);
    $env = $this->createEnvironment('production', EnvironmentType::PRODUCTION, $project);

    activity('variable')
        ->performedOn($env)
        ->causedBy($this->user)
        ->event('context_comment_deleted')
        ->withProperties([
            'environment' => [
                'id' => (string) $env->id,
                'name' => $env->name,
                'type' => $env->type->value,
            ],
            'variable' => [
                'id' => 'secret-app-key',
                'name' => 'APP_KEY',
                'version' => 7,
            ],
        ])
        ->log('Deleted comment for variable "APP_KEY" in "production".');

    Sanctum::actingAs($this->member);

    $this->getJson("/api/v2/projects/{$project->id}/history")
        ->assertOk()
        ->assertJsonPath('data.entries.0.operation', 'comment_deleted')
        ->assertJsonPath('data.entries.0.variable.name', 'APP_KEY')
        ->assertJsonPath('data.entries.0.scope.environment.name', $env->name)
        ->assertJsonPath('data.entries.0.description', 'Deleted comment for variable "APP_KEY" in "production".');
});

test('project history marks variable versions with change reasons', function (): void {
    $org = $this->createOrganization('Ghostbusters', $this->user, [$this->member]);
    $project = $this->createProject('Containment Unit', $org);
    $env = $this->createEnvironment('production', EnvironmentType::PRODUCTION, $project);

    $secret = $env->envSecrets()->create([
        'name' => 'APP_KEY',
        'ciphertext' => 'cipher-two',
        'nonce' => 'nonce-two',
        'alg' => 'xchacha20poly1305',
        'aad' => [],
        'claims' => ['hmac' => 'def'],
        'client_sig' => 'sig-two',
        'version' => 2,
        'last_updated_by' => $this->user->id,
        'last_updated_at' => now(),
    ]);

    $firstVersion = new EnvironmentSecretVersion([
        'name' => $secret->name,
        'version' => 1,
        'ciphertext' => 'cipher-one',
        'nonce' => 'nonce-one',
        'alg' => $secret->alg,
        'aad' => [],
        'claims' => ['hmac' => 'abc'],
        'client_sig' => 'sig-one',
        'is_commented' => false,
        'changed_by' => $this->user->id,
        'created_at' => now()->subHour(),
    ]);
    $firstVersion->secret()->associate($secret);
    $firstVersion->save();

    $latestVersion = new EnvironmentSecretVersion([
        'name' => $secret->name,
        'version' => 2,
        'ciphertext' => $secret->ciphertext,
        'nonce' => $secret->nonce,
        'alg' => $secret->alg,
        'aad' => $secret->aad,
        'claims' => $secret->claims,
        'client_sig' => $secret->client_sig,
        'is_commented' => false,
        'changed_by' => $this->user->id,
        'created_at' => now(),
    ]);
    $latestVersion->secret()->associate($secret);
    $latestVersion->save();

    EnvironmentVariableVersionChangeNote::query()->create([
        'environment_secret_version_id' => $latestVersion->getKey(),
        'ciphertext' => base64_encode('A note explaining the rotation.'),
        'nonce' => base64_encode(random_bytes(24)),
        'alg' => 'xchacha20-poly1305',
        'aad' => [
            'scope' => 'change_note',
        ],
        'claims' => [
            'meta' => [
                'body_length' => 31,
            ],
        ],
        'client_sig' => base64_encode(random_bytes(64)),
        'created_by' => $this->user->id,
    ]);

    Sanctum::actingAs($this->member);

    $this->getJson("/api/v2/projects/{$project->id}/history")
        ->assertOk()
        ->assertJsonPath('data.entries.0.operation', 'updated_with_reason')
        ->assertJsonPath('data.entries.0.variable.name', 'APP_KEY')
        ->assertJsonPath('data.entries.0.scope.environment.name', $env->name)
        ->assertJsonPath('data.entries.0.description', 'Updated variable "APP_KEY" with a reason.');
});
