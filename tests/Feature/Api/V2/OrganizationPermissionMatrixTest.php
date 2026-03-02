<?php

use Laravel\Sanctum\Sanctum;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->owner = $this->createUser(name: 'Owner User', email: 'owner@example.com');
    $this->member = $this->createUser(name: 'Member User', email: 'member@example.com');
    $this->organization = $this->createOrganization(
        name: 'Ghostable Security Org',
        owner: $this->owner,
        members: [$this->member]
    );
    $this->endpoint = "/api/v2/organizations/{$this->organization->id}/permission-matrix";
});

test('organization admin can fetch permission matrix', function () {
    Sanctum::actingAs($this->owner);

    $response = $this->getJson($this->endpoint);

    $response
        ->assertOk()
        ->assertJsonPath('data.organization_id', (string) $this->organization->id)
        ->assertJsonFragment([
            'key' => 'admin',
            'label' => 'Admin',
        ])
        ->assertJsonFragment([
            'key' => 'organization:manage-settings',
            'label' => 'Manage organization settings',
        ]);
});

test('non-admin organization member cannot fetch permission matrix', function () {
    Sanctum::actingAs($this->member);

    $this->getJson($this->endpoint)
        ->assertForbidden();
});
