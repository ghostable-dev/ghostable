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

    $response->assertStatus(200)
        ->assertDontSee('Full name');
});

test('new users can register', function () {
    $response = Livewire::test(Register::class)
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

test('new users return to an intended license claim after registration', function () {
    $claimUrl = 'https://ghostable.test/licenses/example/claim?signature=test';

    $this->withSession(['url.intended' => $claimUrl]);

    Livewire::test(Register::class)
        ->set('email', 'license-claim@example.com')
        ->set('password', 'aComplexP@ssw0rd')
        ->set('password_confirmation', 'aComplexP@ssw0rd')
        ->set('terms', 1)
        ->call('register')
        ->assertHasNoErrors()
        ->assertRedirect($claimUrl);

    $this->assertAuthenticated();
});

test('embedded registrations infer a name from the email address when the field is hidden', function () {
    $response = Livewire::test(Register::class)
        ->set('showNameField', false)
        ->set('email', 'jane.doe@example.com')
        ->set('password', 'aComplexP@ssw0rd')
        ->set('password_confirmation', 'aComplexP@ssw0rd')
        ->set('terms', 1)
        ->call('register');

    $response
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    expect($this->app['auth']->user()?->name)->toBe('Jane Doe');
});

test('registrations with generic mailbox emails can still complete', function () {
    $response = Livewire::test(Register::class)
        ->set('email', 'info@example.com')
        ->set('password', 'aComplexP@ssw0rd')
        ->set('password_confirmation', 'aComplexP@ssw0rd')
        ->set('terms', 1)
        ->call('register');

    $response
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $user = $this->app['auth']->user();

    expect($user)->not->toBeNull()
        ->and($user?->getRawOriginal('name'))->toBeNull()
        ->and($user?->name)->toBe('info@example.com')
        ->and($user?->initials())->toBe('i');
});

test('cli registrations mark the session as verification required', function () {
    $session = CliLoginSession::create([
        'browser_token' => Str::random(64),
        'expires_at' => now()->addMinute(),
    ]);

    Livewire::withQueryParams(['ticket' => $session->id])
        ->test(Register::class)
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
