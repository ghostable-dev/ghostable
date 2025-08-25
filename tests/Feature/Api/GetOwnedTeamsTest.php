<?php

use App\Account\Models\User;
use App\Organization\Actions\CreateOrganization;
use Laravel\Sanctum\Sanctum;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('unauthenticated users cannot fetch owned organizations', function () {
    $this->getJson('/api/v1/owned-organizations')
        ->assertUnauthorized();
});

test('returns only organizations owned by the user', function () {

    $alice = User::factory()->create();
    $alicesOrganization = CreateOrganization::handle('Alice Co.', $alice);

    $bob = User::factory()->create();
    $bobsOrganization = CreateOrganization::handle('Bob Co.', $bob);

    Sanctum::actingAs($alice, ['*']);

    $response = $this->getJson('/api/v1/owned-organizations');

    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonFragment(['id' => $alicesOrganization->id])
        ->assertJsonMissing(['id' => $bobsOrganization->id]);
});

test('response uses OrganizationResource structure', function () {

    $alice = User::factory()->create();

    Sanctum::actingAs($alice, ['*']);

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
