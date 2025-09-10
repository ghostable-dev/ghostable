<?php

use App\Organization\Enums\OrganizationRole;
use Laravel\Sanctum\Sanctum;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('unauthenticated users cannot fetch organization roles', function () {
    $this->getJson('/api/v1/organization-roles')
        ->assertUnauthorized();
});

test('returns all organization roles in the correct format', function () {
    $ray = $this->createUser(name: 'Ray', email: 'ray@ghostbusters.com');
    Sanctum::actingAs($ray);

    $expected = collect(OrganizationRole::cases())
        ->map(fn (OrganizationRole $role) => [
            'key' => $role->value,
            'label' => $role->label(),
            'description' => $role->description(),
        ]);

    $response = $this->getJson('/api/v1/organization-roles');

    $response->assertOk()
        ->assertJsonCount($expected->count(), 'data')
        ->assertExactJson(['data' => $expected->all()]);
});
