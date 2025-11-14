<?php

use Laravel\Sanctum\Sanctum;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->ray = $this->createUser(name: 'Ray', email: 'ray@ghostbusters.com');
    $this->organization = $this->createOrganization(name: 'Ray’s Occult Books', owner: $this->ray);
    $this->website = $this->createProject(name: 'Website', organization: $this->organization);
    $this->endpoint = "/api/v1/projects/{$this->website->id}";
});

test('unauthenticated users cannot fetch project', function () {
    $this->getJson($this->endpoint)
        ->assertUnauthorized();
});

test('returns project for member user', function () {
    Sanctum::actingAs($this->ray);
    $response = $this->getJson($this->endpoint);
    $response->assertOk()->assertJsonFragment(['id' => $this->website->id]);
});

test('users cannot view project of organization they do not belong', function () {
    $peter = $this->createUser(name: 'Peter', email: 'peter@ghostbusters.com');
    Sanctum::actingAs($peter);
    $response = $this->getJson($this->endpoint);
    $response->assertForbidden();
});

test('returns project in the correct format', function () {
    Sanctum::actingAs($this->ray);
    $this->getJson($this->endpoint)
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
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
        ]);
});
