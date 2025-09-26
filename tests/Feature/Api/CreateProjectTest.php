<?php

use App\Organization\Enums\OrganizationRole;
use App\Project\Enums\DeploymentProvider;
use Laravel\Sanctum\Sanctum;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->ray = $this->createUser(name: 'Ray', email: 'ray@ghostbusters.com');
    $this->organization = $this->createOrganization(name: 'Ray’s Occult Books', owner: $this->ray);
    $this->endpoint = "/api/v1/organizations/{$this->organization->id}/projects";
});

test('unauthenticated users cannot create projects', function () {
    $this->getJson($this->endpoint)->assertUnauthorized();
});

test('persists a new project record and returns JSON shape', function () {
    Sanctum::actingAs($this->ray);
    $payload = ['name' => 'Website', 'deployment_provider' => DeploymentProvider::LARAVEL_CLOUD->value];
    $this->postJson($this->endpoint, $payload)
        ->assertStatus(201)
        ->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'slug',
                'organization_id',
                'deployment_provider',
                'environments',
                'created_at',
                'updated_at',
            ],
        ]);

    $project = $this->organization->fresh()->projects()->where($payload)->first();

    $this->assertNotNull($project);
});

describe('authorization', function () {
    beforeEach(function () {
        $this->zuul = $this->createUser(name: 'Zuul', email: 'zuul@gozers-minions.com');
    });

    test('forbids non-members from creating', function () {
        Sanctum::actingAs($this->zuul);
        $this->postJson($this->endpoint, ['name' => 'Website'])->assertForbidden();
    });

    test('forbids members without permission from creating', function () {
        $peter = $this->createUser(name: 'Peter', email: 'perter@ghostbusters.com');
        $peter->organizationMembership()->assignToOrganization(organization: $this->organization, role: OrganizationRole::DEVELOPER_READ_ONLY);
        Sanctum::actingAs($peter);
        $this->postJson($this->endpoint, ['name' => 'Website'])->assertForbidden();
    });
});
