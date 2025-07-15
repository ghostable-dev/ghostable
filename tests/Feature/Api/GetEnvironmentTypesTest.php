<?php

use App\Environment\Enums\EnvironmentType;
use Laravel\Sanctum\Sanctum;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('unauthenticated users cannot fetch environment types', function () {
    $this->getJson('/api/environment-types')
        ->assertUnauthorized();
});

test('returns all environment types in the correct format', function () {
    $ray = $this->createUser(name: 'Ray', email: 'ray@ghostbusters.com');
    Sanctum::actingAs($ray);

    $expected = collect(EnvironmentType::cases())
        ->map(fn (EnvironmentType $type) => [
            'value' => $type->value,
            'label' => $type->label(),
        ]);

    $response = $this->getJson('/api/environment-types');

    $response->assertOk()
        ->assertJsonCount($expected->count(), 'data')
        ->assertExactJson(['data' => $expected->all()]);
});
