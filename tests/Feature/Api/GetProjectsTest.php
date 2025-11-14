<?php

use Laravel\Sanctum\Sanctum;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->ray = $this->createUser(name: 'Ray', email: 'ray@ghostbusters.com');
    $this->organization = $this->createOrganization(name: 'Ray’s Occult Books', owner: $this->ray);
    $this->website = $this->createProject(name: 'Website', organization: $this->organization);
    $this->createProject(name: 'Store', organization: $this->organization);
    $this->endpoint = "/api/v1/organizations/{$this->organization->id}/projects";
});

test('unauthenticated users cannot fetch organization projects', function () {
    $this->getJson($this->endpoint)
        ->assertUnauthorized();
});

test('returns only projects the organization owns', function () {
    $peter = $this->createUser(name: 'Peter', email: 'peter@ghostbusters.com');
    $ghostbusters = $this->createOrganization(name: 'Ghostbusters', owner: $peter);
    $hotline = $this->createProject(name: 'Website', organization: $ghostbusters);
    Sanctum::actingAs($this->ray);
    $this->getJson($this->endpoint)->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonFragment(['id' => $this->website->id])
        ->assertJsonMissing(['id' => $hotline->id]);
});

test('users cannot view organization projects they do not belong to', function () {
    $peter = $this->createUser(name: 'Peter', email: 'peter@ghostbusters.com');
    Sanctum::actingAs($peter);
    $this->getJson($this->endpoint)->assertForbidden();
});

test('returns organization projects in correct structure', function () {
    Sanctum::actingAs($this->ray);
    $this->getJson($this->endpoint)
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                [
                    'id',
                    'name',
                    'slug',
                    'organization_id',
                    'deployment_provider',
                    'stack',
                    'environments',
                    'created_at',
                    'updated_at',
                ],
            ],
        ]);
});
