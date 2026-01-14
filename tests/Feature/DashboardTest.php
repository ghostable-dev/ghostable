<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user without organizations sees create organization call to action', function () {
    $user = $this->createUser('Jane Doe', 'jane@example.com');

    $this->actingAs($user);

    $response = $this->get(route('dashboard'));

    $response->assertOk();
    $response->assertSee('Create Organization');
});

test('suspended users are redirected away from the dashboard', function () {
    $user = $this->createUser('Suspended', 'suspended@example.com');
    $user->suspend();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertRedirect(route('login'));
    $this->assertGuest();
});

test('locked users are redirected away from the dashboard', function () {
    $user = $this->createUser('Locked', 'locked@example.com');
    $user->lock();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertRedirect(route('login'));
    $this->assertGuest();
});
