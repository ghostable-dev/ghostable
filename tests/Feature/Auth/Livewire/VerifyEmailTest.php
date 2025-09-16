<?php

use App\Auth\Actions\Logout;
use App\Auth\Livewire\VerifyEmail;
use App\Auth\Notifications\VerifyEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('redirects verified users to dashboard', function () {
    $user = $this->createUser('Alice', 'alice@example.com');
    $this->actingAs($user);

    Livewire::test(VerifyEmail::class)
        ->call('sendVerification')
        ->assertRedirect(route('dashboard'));
});

it('sends verification email to unverified users', function () {
    $user = $this->createUser('Bob', 'bob@example.com');
    $user->email_verified_at = null;
    $user->save();

    Notification::fake();
    $this->actingAs($user);

    Livewire::test(VerifyEmail::class)
        ->call('sendVerification');

    Notification::assertSentTo($user, VerifyEmailNotification::class);
});

it('logs users out', function () {
    $user = $this->createUser('Carl', 'carl@example.com');
    $this->actingAs($user);

    $logout = Mockery::spy(Logout::class);

    Livewire::test(VerifyEmail::class)
        ->call('logout', $logout)
        ->assertRedirect('/');

    $logout->shouldHaveReceived('__invoke');
});
