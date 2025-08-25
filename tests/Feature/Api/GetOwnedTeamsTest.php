<?php

use Laravel\Sanctum\Sanctum;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->ray = $this->createUser(name: 'Ray', email: 'ray@ghostbusters.com');
    $this->organization = $this->createOrganization(name: 'Ray’s Occult Books', owner: $this->ray);
});

test('unauthenticated users cannot fetch owned organizations', function () {
    $this->getJson('/api/v1/owned-organizations')
        ->assertUnauthorized();
});

test('returns only organizations owned by the user', function () {
    $peter = $this->createUser(name: 'Peter', email: 'perter@ghostbusters.com');
    $ghostbusters = $this->createOrganization(name: 'Ghostbusters', owner: $peter);

    Sanctum::actingAs($this->ray, ['*']);

    $response = $this->getJson('/api/v1/owned-organizations');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment(['id' => $this->organization->id])
        ->assertJsonMissing(['id' => $ghostbusters->id]);
});

test('response uses OrganizationResource structure', function () {

    Sanctum::actingAs($this->ray, ['*']);

    $this->getJson('/api/v1/owned-organizations')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                [
                    'id',
                    'name',
                    'slug',
                    'is_personal',
                    'created_at',
                    'updated_at',
                ],
            ],
        ]);
});
