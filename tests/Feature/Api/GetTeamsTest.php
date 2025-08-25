<?php

use Laravel\Sanctum\Sanctum;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('unauthenticated users cannot fetch organizations', function () {
    $this->getJson('/api/v1/organizations')
        ->assertUnauthorized();
});

test('returns only organizations the user is a member of', function () {
    $ray = $this->createUser(name: 'Ray', email: 'ray@ghostbusters.com');
    $peter = $this->createUser(name: 'Peter', email: 'peter@ghostbusters.com');

    Sanctum::actingAs($ray);

    $response = $this->getJson('/api/v1/organizations');
    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment(['id' => $ray->personalOrganization()->id])
        ->assertJsonMissing(['id' => $peter->personalOrganization()->id]);
});

test('returns organizations the user is a member of (regardless of role)', function () {
    $ray = $this->createUser(name: 'Ray', email: 'ray@ghostbusters.com');
    $egon = $this->createUser(name: 'Egon', email: 'egon@ghostbusters.com');
    $bookstore = $this->createOrganization(
        name: 'Rays Occult',
        owner: $ray,
        members: [$egon]
    );
    $egon = $egon->fresh();

    Sanctum::actingAs($egon);

    $response = $this->getJson('/api/v1/organizations');
    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonFragment(['id' => $egon->personalOrganization()->id])
        ->assertJsonFragment(['id' => $bookstore->id]);
});

test('response uses OrganizationResource structure', function () {

    $ray = $this->createUser(name: 'Ray', email: 'ray@ghostbusters.com');

    Sanctum::actingAs($ray);

    $this->getJson('/api/v1/organizations')
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
