<?php

use App\Environment\Enums\EnvironmentType;
use App\Environment\Models\EnvironmentSecretVersion;
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
