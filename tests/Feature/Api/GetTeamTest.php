<?php

use Laravel\Sanctum\Sanctum;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('unauthenticated users cannot fetch a organization', function () {
    $this->getJson('/api/v1/organizations/123')
        ->assertUnauthorized();
});

test('returns organization for member user', function () {
    $ray = $this->createUser(name: 'Ray', email: 'ray@ghostbusters.com');
    Sanctum::actingAs($ray);
    $response = $this->getJson('/api/v1/organizations/'.$ray->personalOrganization()->id);
    $response->assertOk()->assertJsonFragment(['id' => $ray->personalOrganization()->id]);
});

test('users cannot view organizations they do not belong to', function () {
    $ray = $this->createUser(name: 'Ray', email: 'ray@ghostbusters.com');
    $peter = $this->createUser(name: 'Peter', email: 'peter@ghostbusters.com');
    Sanctum::actingAs($ray);
    $response = $this->getJson('/api/v1/organizations/'.$peter->personalOrganization()->id);
    $response->assertForbidden();
});

test('returns organization in the correct format', function () {
    $ray = $this->createUser(name: 'Ray', email: 'ray@ghostbusters.com');
    Sanctum::actingAs($ray);
    $this->getJson('/api/v1/organizations/'.$ray->personalOrganization()->id)
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'slug',
                'is_personal',
                'created_at',
                'updated_at',
            ],
        ]);
});
