<?php

use App\Environment\Enums\EnvironmentType;
use App\Environment\Models\EnvironmentSecretVersion;
use App\Environment\Models\EnvironmentVariableVersionChangeNote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = $this->createUser('Dana', 'dana@ghostable.com');
    $this->member = $this->createUser('Louis', 'louis@ghostable.com');
});

test('unauthenticated users cannot view environment history', function (): void {
    $org = $this->createOrganization('Ghostbusters', $this->user);
    $project = $this->createProject('Containment Unit', $org);
    $env = $this->createEnvironment('production', EnvironmentType::PRODUCTION, $project);

    $this->getJson("/api/v2/projects/{$project->id}/environments/{$env->name}/history")
        ->assertUnauthorized();
});

test('members can view environment history', function (): void {
    $org = $this->createOrganization('Ghostbusters', $this->user, [$this->member]);
    $project = $this->createProject('Containment Unit', $org);
    $env = $this->createEnvironment('production', EnvironmentType::PRODUCTION, $project);

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
    $env->refresh();

    $response = $this->getJson("/api/v2/projects/{$project->id}/environments/{$env->name}/history");

    $response->assertOk();

    $entries = $response->json('data.entries') ?? [];
    expect($entries)->toBeArray();
    expect($entries)->not()->toBeEmpty();
    expect(data_get($entries[0], 'variable.name'))->toBe($secret->name);
});

test('non-members cannot view environment history', function (): void {
    $org = $this->createOrganization('Ghostbusters', $this->user);
    $project = $this->createProject('Containment Unit', $org);
    $env = $this->createEnvironment('production', EnvironmentType::PRODUCTION, $project);

    Sanctum::actingAs($this->member);

    $this->getJson("/api/v2/projects/{$project->id}/environments/{$env->name}/history")
        ->assertForbidden();
});

test('environment history includes environment key reshare events', function (): void {
    $org = $this->createOrganization('Ghostbusters', $this->user, [$this->member]);
    $project = $this->createProject('Containment Unit', $org);
    $env = $this->createEnvironment('production', EnvironmentType::PRODUCTION, $project);

    activity('variable')
        ->performedOn($env)
        ->causedBy($this->user)
        ->event('environment_key_reshared')
        ->withProperties([
            'environment_key' => [
                'version' => 2,
                'fingerprint' => 'abc123',
            ],
        ])
        ->log('Re-shared environment key.');

    Sanctum::actingAs($this->member);

    $response = $this->getJson("/api/v2/projects/{$project->id}/environments/{$env->name}/history");

    $response->assertOk()
        ->assertJsonPath('data.entries.0.operation', 'reshared')
        ->assertJsonPath('data.entries.0.variable.name', 'Environment key')
        ->assertJsonPath('data.entries.0.variable.version', 2);
});

test('environment history includes key re-share lifecycle events', function (): void {
    $org = $this->createOrganization('Ghostbusters', $this->user, [$this->member]);
    $project = $this->createProject('Containment Unit', $org);
    $env = $this->createEnvironment('production', EnvironmentType::PRODUCTION, $project);

    activity('variable')
        ->performedOn($env)
        ->event('environment_key_reshare_requested')
        ->withProperties([
            'environment_key' => [
                'version' => 3,
            ],
        ])
        ->log('Requested key re-share.');

    Sanctum::actingAs($this->member);

    $response = $this->getJson("/api/v2/projects/{$project->id}/environments/{$env->name}/history");

    $response->assertOk()
        ->assertJsonPath('data.entries.0.operation', 'reshare_requested')
        ->assertJsonPath('data.entries.0.variable.name', 'Environment key re-share')
        ->assertJsonPath('data.entries.0.variable.version', 3);
});

test('environment history includes variable context activity entries', function (): void {
    $org = $this->createOrganization('Ghostbusters', $this->user, [$this->member]);
    $project = $this->createProject('Containment Unit', $org);
    $env = $this->createEnvironment('production', EnvironmentType::PRODUCTION, $project);

    activity('variable')
        ->performedOn($env)
        ->causedBy($this->user)
        ->event('context_comment_added')
        ->withProperties([
            'environment' => [
                'id' => (string) $env->id,
                'name' => $env->name,
                'type' => $env->type->value,
            ],
            'variable' => [
                'id' => 'secret-app-key',
                'name' => 'APP_KEY',
                'version' => 4,
            ],
        ])
        ->log('Added comment for variable "APP_KEY" in "production".');

    Sanctum::actingAs($this->member);

    $this->getJson("/api/v2/projects/{$project->id}/environments/{$env->name}/history")
        ->assertOk()
        ->assertJsonPath('data.entries.0.operation', 'comment_added')
        ->assertJsonPath('data.entries.0.variable.name', 'APP_KEY')
        ->assertJsonPath('data.entries.0.variable.version', 4)
        ->assertJsonPath('data.entries.0.description', 'Added comment for variable "APP_KEY" in "production".');
});

test('environment history includes deleted comment activity entries', function (): void {
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
                'version' => 4,
            ],
        ])
        ->log('Deleted comment for variable "APP_KEY" in "production".');

    Sanctum::actingAs($this->member);

    $this->getJson("/api/v2/projects/{$project->id}/environments/{$env->name}/history")
        ->assertOk()
        ->assertJsonPath('data.entries.0.operation', 'comment_deleted')
        ->assertJsonPath('data.entries.0.variable.name', 'APP_KEY')
        ->assertJsonPath('data.entries.0.description', 'Deleted comment for variable "APP_KEY" in "production".');
});

test('environment history marks variable versions with change reasons', function (): void {
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

    $this->getJson("/api/v2/projects/{$project->id}/environments/{$env->name}/history")
        ->assertOk()
        ->assertJsonPath('data.entries.0.operation', 'updated_with_reason')
        ->assertJsonPath('data.entries.0.variable.name', 'APP_KEY')
        ->assertJsonPath('data.entries.0.variable.version', 2)
        ->assertJsonPath('data.entries.0.description', 'Updated variable "APP_KEY" with a reason.');
});

test('environment history activity uses desktop source when requested by the desktop client', function (): void {
    $org = $this->createOrganization('Ghostbusters', $this->user, [$this->member]);
    $project = $this->createProject('Containment Unit', $org);
    $env = $this->createEnvironment('production', EnvironmentType::PRODUCTION, $project);

    Sanctum::actingAs($this->member);

    $this->withHeaders([
        'X-Ghostable-Client-Type' => 'desktop',
    ])->getJson("/api/v2/projects/{$project->id}/environments/{$env->name}/history")
        ->assertOk();

    $activity = Activity::query()
        ->where('event', 'history_viewed')
        ->latest()
        ->first();

    expect($activity)->not->toBeNull();
    expect(data_get($activity->properties, 'source'))->toBe('desktop');
});
