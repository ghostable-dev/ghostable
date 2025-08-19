<?php

use Laravel\Sanctum\Sanctum;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('unauthenticated users cannot fetch teams', function () {
    $this->getJson('/api/v1/teams')
        ->assertUnauthorized();
});

test('returns only teams the user is a member of', function () {
    $ray = $this->createUser(name: 'Ray', email: 'ray@ghostbusters.com');
    $peter = $this->createUser(name: 'Peter', email: 'peter@ghostbusters.com');

    Sanctum::actingAs($ray);

    $response = $this->getJson('/api/v1/teams');
    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment(['id' => $ray->personalTeam()->id])
        ->assertJsonMissing(['id' => $peter->personalTeam()->id]);
});

test('returns teams the user is a member of (regardless of role)', function () {
    $ray = $this->createUser(name: 'Ray', email: 'ray@ghostbusters.com');
    $egon = $this->createUser(name: 'Egon', email: 'egon@ghostbusters.com');
    $bookstore = $this->createTeam(
        name: 'Rays Occult',
        owner: $ray,
        members: [$egon]
    );
    $egon = $egon->fresh();

    Sanctum::actingAs($egon);

    $response = $this->getJson('/api/v1/teams');
    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonFragment(['id' => $egon->personalTeam()->id])
        ->assertJsonFragment(['id' => $bookstore->id]);
});

test('response uses TeamResource structure', function () {

    $ray = $this->createUser(name: 'Ray', email: 'ray@ghostbusters.com');

    Sanctum::actingAs($ray);

    $this->getJson('/api/v1/teams')
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
