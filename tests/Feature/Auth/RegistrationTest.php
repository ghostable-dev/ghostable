<?php

use App\Account\Livewire\Register;
use App\Auth\Enums\CliLoginSessionStatus;
use App\Auth\Models\CliLoginSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    $response = Livewire::test(Register::class)
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('password', 'aComplexP@ssw0rd')
        ->set('password_confirmation', 'aComplexP@ssw0rd')
        ->set('terms', 1)
        ->call('register');

    $response
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});

test('cli registrations mark the session as verification required', function () {
    $session = CliLoginSession::create([
        'browser_token' => Str::random(64),
        'expires_at' => now()->addMinute(),
    ]);

    Livewire::withQueryParams(['ticket' => $session->id])
        ->test(Register::class)
        ->set('name', 'CLI User')
        ->set('email', 'cli@example.com')
        ->set('password', 'aComplexP@ssw0rd')
        ->set('password_confirmation', 'aComplexP@ssw0rd')
        ->set('terms', 1)
        ->call('register')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();

    $session->refresh();

    expect($session->status)->toBe(CliLoginSessionStatus::VerificationRequired)
        ->and($session->user_id)->not->toBeNull();
});
