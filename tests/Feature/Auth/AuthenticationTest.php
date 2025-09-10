<?php

use App\Account\Models\User;
use App\Auth\Livewire\Login;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\RecoveryCode;
use Livewire\Livewire;
use PragmaRX\Google2FA\Google2FA;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->ray = $this->createUser(name: 'Ray', email: 'ray@ghostbusters.com');
    $this->organization = $this->createOrganization(name: 'Ray’s Occult Books', owner: $this->ray);
});

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('users can authenticate using the login screen', function () {
    $response = Livewire::test(Login::class)
        ->set('email', $this->ray->email)
        ->set('password', 'password')
        ->call('login');

    $response
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});

test('single-organization users are not prompted to select a organization after login', function () {
    Livewire::test(Login::class)
        ->set('email', $this->ray->email)
        ->set('password', 'password')
        ->call('login');

    expect(session()->has('show-organization-switcher'))->toBeFalse();
});

test('multi-organization users are prompted to select a organization after login', function () {
    $this->createOrganization(name: 'Ghostbusters', owner: $this->ray);

    Livewire::test(Login::class)
        ->set('email', $this->ray->email)
        ->set('password', 'password')
        ->call('login');

    expect(session()->has('show-organization-switcher'))->toBeTrue();
});

test('multi-organization users are prompted to select a organization after two factor login', function () {
    $provider = app(TwoFactorAuthenticationProvider::class);
    $secret = $provider->generateSecretKey();
    $code = app(Google2FA::class)->getCurrentOtp($secret);

    $user = User::factory()->create([
        'two_factor_secret' => encrypt($secret),
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => encrypt(json_encode([RecoveryCode::generate()])),
    ]);

    $firstOrg = $this->createOrganization(name: 'First Organization', owner: $user);
    $secondOrg = $this->createOrganization(name: 'Another Organization', owner: $user);

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'password')
        ->call('login')
        ->assertRedirect(route('two-factor.login', absolute: false));

    $this->post('/two-factor-challenge', [
        'code' => $code,
    ])->assertRedirect('/dashboard');

    expect(session()->has('show-organization-switcher'))->toBeTrue();
    $this->assertAuthenticatedAs($user);
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $response = Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'wrong-password')
        ->call('login');

    $response->assertHasErrors('email');

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $response->assertRedirect('/');

    $this->assertGuest();
});
