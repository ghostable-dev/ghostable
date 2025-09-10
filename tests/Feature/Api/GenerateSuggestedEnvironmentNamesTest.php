<?php

use App\Environment\Enums\EnvironmentType;
use Laravel\Sanctum\Sanctum;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->ray = $this->createUser(name: 'Ray', email: 'ray@ghostbusters.com');
    $this->organization = $this->createOrganization(name: 'Ray’s Occult Books', owner: $this->ray);
    $this->project = $this->createProject(name: 'Website', organization: $this->organization);
    $this->endpoint = "/api/v1/projects/{$this->project->id}/generate-suggested-environment-names";
});

test('unauthenticated users cannot generate suggestions', function () {
    $this->postJson($this->endpoint, ['type' => EnvironmentType::DEVELOPMENT->value])
        ->assertUnauthorized();
});

test('returns suggestions for member user', function () {
    Sanctum::actingAs($this->ray);

    $this->postJson($this->endpoint, ['type' => EnvironmentType::DEVELOPMENT->value])
        ->assertOk()
        ->assertJsonCount(5, 'data')
        ->assertJsonFragment(['name' => 'development'])
        ->assertJsonFragment(['name' => 'development-r']);
});

test('users cannot generate suggestions for projects they do not belong to', function () {
    $peter = $this->createUser(name: 'Peter', email: 'peter@ghostbusters.com');
    Sanctum::actingAs($peter);

    $this->postJson($this->endpoint, ['type' => EnvironmentType::DEVELOPMENT->value])
        ->assertForbidden();
});

test('returns suggestions in correct structure', function () {
    Sanctum::actingAs($this->ray);

    $this->postJson($this->endpoint, ['type' => EnvironmentType::DEVELOPMENT->value])
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                [
                    'name',
                ],
            ],
        ]);
});
