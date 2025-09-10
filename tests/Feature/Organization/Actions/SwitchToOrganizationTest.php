<?php

use App\Organization\Actions\SwitchToOrganization;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can switch to organization they belong to', function () {
    $owner = $this->createUser('Owner', 'owner@example.com');
    $organization = $this->createOrganization('Acme', $owner);
    $this->actingAs($owner);

    SwitchToOrganization::handle($organization);

    expect(session('current_organization_id'))->toBe($organization->id);
});

test('user cannot switch to organization they do not belong to', function () {
    $owner = $this->createUser('Owner', 'owner@example.com');
    $other = $this->createUser('Other', 'other@example.com');
    $organization = $this->createOrganization('Acme', $owner);
    $this->actingAs($other);

    SwitchToOrganization::handle($organization);
})->throws(AuthorizationException::class);
