<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->endpoint = '/api/v2/cli/login';
});

test('suspended users cannot login via cli', function (): void {
    $user = $this->createUser('Suspended', 'suspended@example.com');
    $user->suspend();

    $this->postJson($this->endpoint, [
        'email' => $user->email,
        'password' => 'password',
    ])->assertForbidden();
});

test('locked users cannot login via cli', function (): void {
    $user = $this->createUser('Locked', 'locked@example.com');
    $user->lock();

    $this->postJson($this->endpoint, [
        'email' => $user->email,
        'password' => 'password',
    ])->assertForbidden();
});
