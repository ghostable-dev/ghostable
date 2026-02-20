<?php

use App\Environment\Enums\EnvironmentType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = $this->createUser('Egon', 'egon@ghostable.com');
    $this->member = $this->createUser('Peter', 'peter@ghostable.com');
});

test('unauthenticated users cannot list environment keys', function (): void {
    $org = $this->createOrganization('Ghostbusters', $this->user);
    $project = $this->createProject('Containment Unit', $org);
    $env = $this->createEnvironment('production', EnvironmentType::PRODUCTION, $project);

    $this->getJson("/api/v2/projects/{$project->id}/environments/{$env->name}/keys")
        ->assertUnauthorized();
});

test('members can list environment keys', function (): void {
    $org = $this->createOrganization('Ghostbusters', $this->user, [$this->member]);
    $project = $this->createProject('Containment Unit', $org);
    $env = $this->createEnvironment('production', EnvironmentType::PRODUCTION, $project);

    $this->createZeroKnowledgeVariables($env, amount: 2, createdBy: $this->member);
    $firstSecret = $env->envSecrets()->first();
    expect($firstSecret)->not->toBeNull();
    $firstSecret->update(['is_commented' => true]);

    Sanctum::actingAs($this->member);
    $this->member->refresh();
    $env->refresh();

    $response = $this->getJson("/api/v2/projects/{$project->id}/environments/{$env->name}/keys");

    $response->assertOk();

    $data = $response->json('data') ?? [];
    expect($data)->toBeArray();
    expect(collect($data)->pluck('name'))->toHaveCount(2);
    expect(collect($data)->contains(function (array $row) use ($firstSecret): bool {
        return ($row['name'] ?? null) === $firstSecret->name
            && ($row['is_commented'] ?? null) === true;
    }))->toBeTrue();
});

test('non-members cannot list environment keys', function (): void {
    $org = $this->createOrganization('Ghostbusters', $this->user);
    $project = $this->createProject('Containment Unit', $org);
    $env = $this->createEnvironment('production', EnvironmentType::PRODUCTION, $project);

    Sanctum::actingAs($this->member);

    $this->getJson("/api/v2/projects/{$project->id}/environments/{$env->name}/keys")
        ->assertForbidden();
});
