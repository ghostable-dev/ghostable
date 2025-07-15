<?php

use Laravel\Sanctum\Sanctum;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('unauthenticated users cannot fetch a team', function () {
    $this->getJson('/api/teams/123')
        ->assertUnauthorized();
});

test('returns team for member user', function () {
    $ray = $this->createUser(name: 'Ray', email: 'ray@ghostbusters.com');
    
    Sanctum::actingAs($ray);
    
    $response = $this->getJson('/api/teams/' . $ray->personalTeam()->id);

    $response->assertOk()
        ->assertJsonFragment(['id' => $ray->personalTeam()->id]);
});

test('users cannot view teams they do not belong to', function () {
    $ray = $this->createUser(name: 'Ray', email: 'ray@ghostbusters.com');
    $peter = $this->createUser(name: 'Peter', email: 'peter@ghostbusters.com');
    
    Sanctum::actingAs($ray);
    
    $response = $this->getJson('/api/teams/' . $peter->personalTeam()->id);

    $response->assertForbidden();
});

test('returns team in the correct format', function () {
    $ray = $this->createUser(name: 'Ray', email: 'ray@ghostbusters.com');
    
    Sanctum::actingAs($ray);

    $this->getJson('/api/teams/' . $ray->personalTeam()->id)
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                    'id',
                    'name',
                    'slug',
                    'is_personal',
                    'created_at',
                    'updated_at'
            ],
        ]);
});