<?php

use App\Team\Enums\TeamRole;
use Laravel\Sanctum\Sanctum;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('unauthenticated users cannot fetch team roles', function () {
    $this->getJson('/api/v1/team-roles')
        ->assertUnauthorized();
});

test('returns all team roles in the correct format', function () {
    $ray = $this->createUser(name: 'Ray', email: 'ray@ghostbusters.com');
    Sanctum::actingAs($ray);

    $expected = collect(TeamRole::cases())
        ->map(fn (TeamRole $role) => [
            'key' => $role->value,
            'label' => $role->label(),
            'description' => $role->description(),
        ]);

    $response = $this->getJson('/api/v1/team-roles');

    $response->assertOk()
        ->assertJsonCount($expected->count(), 'data')
        ->assertExactJson(['data' => $expected->all()]);
});
