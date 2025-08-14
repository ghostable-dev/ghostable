<?php

use App\Environment\Enums\EnvFileFormat;
use Laravel\Sanctum\Sanctum;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('unauthenticated users cannot fetch environment formats', function () {
    $this->getJson('/api/environment-formats')
        ->assertUnauthorized();
});

test('returns all environment formats in the correct format', function () {
    $ray = $this->createUser(name: 'Ray', email: 'ray@ghostbusters.com');
    Sanctum::actingAs($ray);

    $expected = collect(EnvFileFormat::cases())
        ->map(fn (EnvFileFormat $format) => [
            'value' => $format->value,
            'label' => $format->label(),
        ]);

    $response = $this->getJson('/api/environment-formats');

    $response->assertOk()
        ->assertJsonCount($expected->count(), 'data')
        ->assertExactJson(['data' => $expected->all()]);
});
