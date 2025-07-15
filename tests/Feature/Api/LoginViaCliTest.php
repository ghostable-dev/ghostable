<?php

use App\Account\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('email and password are required', function () {
    $this->postJson('/api/cli/login', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email', 'password']);
});

test('fails with invalid credentials', function () {
    $user = User::factory()->create();
    $this->postJson('/api/cli/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])->assertStatus(401)
        ->assertJson([
            'message' => 'Invalid credentials.',
        ]);
});

test('returns token, user and teams on successful login', function () {
    $user = User::factory()->create();
    $response = $this->postJson('/api/cli/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'token',
            'user' => ['id', 'name', 'email'],
            'teams' => [
                ['id', 'name'],
            ],
        ]);

    // The returned user data matches
    expect($response->json('user.email'))->toBe($user->email);

    // And a non-empty token was issued
    expect(strlen($response->json('token')))->toBeGreaterThan(20);
});
