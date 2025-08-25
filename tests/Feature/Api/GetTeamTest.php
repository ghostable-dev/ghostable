<?php

use Laravel\Sanctum\Sanctum;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('unauthenticated users cannot fetch a organization', function () {
    $this->getJson('/api/v1/organizations/123')
        ->assertUnauthorized();
});

test('returns organization for member user', function () {
    $ray = $this->createUser(name: 'Ray', email: 'ray@ghostbusters.com');
    $org = $this->createOrganization(name: 'Ray’s Occult Books', owner: $ray);
    Sanctum::actingAs($ray);
    $response = $this->getJson('/api/v1/organizations/'.$org->id);
    $response->assertOk()->assertJsonFragment(['id' => $org->id]);
});

test('users cannot view organizations they do not belong to', function () {
    $ray = $this->createUser(name: 'Ray', email: 'ray@ghostbusters.com');
    $peter = $this->createUser(name: 'Peter', email: 'peter@ghostbusters.com');
    $org = $this->createOrganization(name: 'Ghostbusters', owner: $peter);
    Sanctum::actingAs($ray);
    $response = $this->getJson('/api/v1/organizations/'.$org->id);
    $response->assertForbidden();
});

test('returns organization in the correct format', function () {
    $ray = $this->createUser(name: 'Ray', email: 'ray@ghostbusters.com');
    $org = $this->createOrganization(name: 'Ray’s Occult Books', owner: $ray);
    Sanctum::actingAs($ray);
    $this->getJson('/api/v1/organizations/'.$org->id)
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
