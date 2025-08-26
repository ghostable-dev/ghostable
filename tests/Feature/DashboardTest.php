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
