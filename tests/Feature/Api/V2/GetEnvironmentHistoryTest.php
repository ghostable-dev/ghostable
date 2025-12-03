<?php

use App\Environment\Enums\EnvironmentType;
use App\Environment\Models\EnvironmentSecretVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

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
