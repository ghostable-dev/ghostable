<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = $this->createUser('Dana', 'dana@ghostable.com');
});

test('environment file formats are returned', function (): void {
    Sanctum::actingAs($this->user);

    $response = $this->getJson('/api/v2/environment-formats');

    $response->assertOk();

    $data = $response->json('data') ?? [];
    expect($data)->toBeArray();
    expect($data)->not()->toBeEmpty();
});
