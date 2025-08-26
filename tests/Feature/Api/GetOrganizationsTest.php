<?php

use Laravel\Sanctum\Sanctum;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->ray = $this->createUser(name: 'Ray', email: 'ray@ghostbusters.com');
    $this->organization = $this->createOrganization(name: 'Ray’s Occult Books', owner: $this->ray);
});

test('unauthenticated users cannot fetch organizations', function () {
    $this->getJson('/api/v1/organizations')
        ->assertUnauthorized();
});

test('returns only organizations the user is a member of', function () {
    $peter = $this->createUser(name: 'Peter', email: 'peter@ghostbusters.com');
    $ghostbusters = $this->createOrganization(name: 'Ghostbusters', owner: $peter);
    $ray = $this->ray->fresh();

    Sanctum::actingAs($ray);

    $response = $this->getJson('/api/v1/organizations');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment(['id' => $this->organization->id])
        ->assertJsonMissing(['id' => $ghostbusters->id]);
});

test('returns organizations the user is a member of (regardless of role)', function () {
    $egon = $this->createUser(name: 'Egon', email: 'egon@ghostbusters.com');
    $egonLabs = $this->createOrganization(name: 'Egon Labs', owner: $egon);
    $ghostbusters = $this->createOrganization(name: 'Ghostbusters', owner: $this->ray, members: [$egon]);
    $egon = $egon->fresh();

    Sanctum::actingAs($egon);

    $response = $this->getJson('/api/v1/organizations');

    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonFragment(['id' => $egonLabs->id])
        ->assertJsonFragment(['id' => $ghostbusters->id]);
});

test('response uses OrganizationResource structure', function () {
    $egon = $this->createUser(name: 'Egon', email: 'egon@ghostbusters.com');
    $this->createOrganization(name: 'Egon Labs', owner: $egon);
    $egon = $egon->fresh();

    Sanctum::actingAs($egon);

    $this->getJson('/api/v1/organizations')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                [
                    'id',
                    'name',
                    'slug',
                    'created_at',
                    'updated_at',
                ],
            ],
        ]);
});
