<?php

use App\Account\Models\User;
use App\Team\Actions\CreateTeam;
use Laravel\Sanctum\Sanctum;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('unauthenticated users cannot fetch owned teams', function () {
    $this->getJson('/api/owned-teams')
        ->assertUnauthorized();
});

test('returns only teams owned by the user', function () {

    $alice = User::factory()->create();
    $alicesTeam = CreateTeam::handle('Alice Co.', $alice);

    $bob = User::factory()->create();
    $bobsTeam = CreateTeam::handle('Bob Co.', $bob);

    Sanctum::actingAs($alice, ['*']);

    $response = $this->getJson('/api/owned-teams');

    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonFragment(['id' => $alicesTeam->id])
        ->assertJsonMissing(['id' => $bobsTeam->id]);
});

test('response uses TeamResource structure', function () {

    $alice = User::factory()->create();

    Sanctum::actingAs($alice, ['*']);

    $this->getJson('/api/owned-teams')
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
