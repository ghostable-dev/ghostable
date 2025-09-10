<?php

use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\RecoveryCode;
use PragmaRX\Google2FA\Google2FA;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->ray = $this->createUser(name: 'Ray', email: 'ray@ghostbusters.com');
    $this->organization = $this->createOrganization(name: 'Ray’s Occult Books', owner: $this->ray);
});

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
    $this->postJson('/api/v1/cli/login', [
        'email' => $this->ray->email,
        'password' => 'wrong-password',
    ])->assertStatus(401)
        ->assertJson([
            'message' => 'Invalid credentials.',
        ]);
});

test('returns token, user and organizations on successful login', function () {
    $response = $this->postJson('/api/v1/cli/login', [
        'email' => $this->ray->email,
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
    expect($response->json('user.email'))->toBe($this->ray->email);

    // And a non-empty token was issued
    expect(strlen($response->json('token')))->toBeGreaterThan(20);
});

test('two factor code is required when enabled', function () {
    $provider = app(TwoFactorAuthenticationProvider::class);
    $secret = $provider->generateSecretKey();
    $this->ray->two_factor_secret = encrypt($secret);
    $this->ray->two_factor_confirmed_at = now();
    $this->ray->two_factor_recovery_codes = encrypt(json_encode([RecoveryCode::generate()]));
    $this->ray->save();

    $this->postJson('/api/v1/cli/login', [
        'email' => $this->ray->email,
        'password' => 'password',
    ])->assertOk()->assertJson([
        'two_factor' => true,
    ]);
});

test('can login with valid two factor code', function () {

    $provider = app(TwoFactorAuthenticationProvider::class);
    $secret = $provider->generateSecretKey();
    $this->ray->two_factor_secret = encrypt($secret);
    $this->ray->two_factor_confirmed_at = now();
    $this->ray->two_factor_recovery_codes = encrypt(json_encode([RecoveryCode::generate()]));
    $this->ray->save();

    $code = app(Google2FA::class)->getCurrentOtp($secret);

    $response = $this->postJson('/api/v1/cli/login', [
        'email' => $this->ray->email,
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
    $this->ray->two_factor_secret = encrypt($secret);
    $this->ray->two_factor_confirmed_at = now();
    $this->ray->two_factor_recovery_codes = encrypt(json_encode([RecoveryCode::generate()]));
    $this->ray->save();

    $this->postJson('/api/v1/cli/login', [
        'email' => $this->ray->email,
        'password' => 'password',
        'code' => '123456',
    ])->assertStatus(422)->assertJson([
        'message' => 'Invalid two-factor authentication code.',
    ]);
});
