<?php

use App\Account\Models\User;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\RecoveryCode;
use PragmaRX\Google2FA\Google2FA;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('email and password are required', function () {
    $response = $this->postJson('/api/v1/cli/login', []);

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'GHO-VAL-0001')
        ->assertJsonStructure([
            'error' => [
                'fields' => ['email', 'password'],
            ],
        ]);
});

test('fails with invalid credentials', function () {
    $user = User::factory()->create();
    $this->postJson('/api/v1/cli/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])->assertStatus(401)
        ->assertJson([
            'message' => 'Invalid credentials.',
        ]);
});

test('returns token, user and organizations on successful login', function () {
    $user = User::factory()->create();
    $response = $this->postJson('/api/v1/cli/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'token',
            'user' => ['id', 'name', 'email'],
            'organizations' => [
                ['id', 'name'],
            ],
        ]);

    // The returned user data matches
    expect($response->json('user.email'))->toBe($user->email);

    // And a non-empty token was issued
    expect(strlen($response->json('token')))->toBeGreaterThan(20);
});

test('two factor code is required when enabled', function () {
    $provider = app(TwoFactorAuthenticationProvider::class);
    $secret = $provider->generateSecretKey();

    $user = User::factory()->create([
        'two_factor_secret' => encrypt($secret),
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => encrypt(json_encode([RecoveryCode::generate()])),
    ]);

    $this->postJson('/api/v1/cli/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertOk()->assertJson([
        'two_factor' => true,
    ]);
});

test('can login with valid two factor code', function () {
    $provider = app(TwoFactorAuthenticationProvider::class);
    $secret = $provider->generateSecretKey();
    $code = app(Google2FA::class)->getCurrentOtp($secret);

    $user = User::factory()->create([
        'two_factor_secret' => encrypt($secret),
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => encrypt(json_encode([RecoveryCode::generate()])),
    ]);

    $response = $this->postJson('/api/v1/cli/login', [
        'email' => $user->email,
        'password' => 'password',
        'code' => $code,
    ]);

    $response->assertOk()->assertJsonStructure([
        'token',
        'user' => ['id', 'name', 'email'],
        'organizations' => [
            ['id', 'name'],
        ],
    ]);
});

test('fails with invalid two factor code', function () {
    $provider = app(TwoFactorAuthenticationProvider::class);
    $secret = $provider->generateSecretKey();

    $user = User::factory()->create([
        'two_factor_secret' => encrypt($secret),
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => encrypt(json_encode([RecoveryCode::generate()])),
    ]);

    $this->postJson('/api/v1/cli/login', [
        'email' => $user->email,
        'password' => 'password',
        'code' => '123456',
    ])->assertStatus(422)->assertJson([
        'message' => 'Invalid two-factor authentication code.',
    ]);
});

test('cannot login with recovery code', function () {
    $provider = app(TwoFactorAuthenticationProvider::class);
    $secret = $provider->generateSecretKey();
    $recoveryCode = RecoveryCode::generate();

    $user = User::factory()->create([
        'two_factor_secret' => encrypt($secret),
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => encrypt(json_encode([$recoveryCode])),
    ]);

    $response = $this->postJson('/api/v1/cli/login', [
        'email' => $user->email,
        'password' => 'password',
        'recovery_code' => $recoveryCode,
    ]);

    $response->assertOk()->assertJson([
        'two_factor' => true,
    ]);

    expect($response->json('token'))->toBeNull();
});
