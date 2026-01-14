<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = $this->createUser('Leslie', 'leslie@ghostable.com');
    $this->endpoint = '/api/v2/organizations';
});

test('unauthenticated users cannot list organizations', function (): void {
    $this->getJson($this->endpoint)->assertUnauthorized();
});

test('returns organizations the user belongs to', function (): void {
    Sanctum::actingAs($this->user);

    $first = $this->createOrganization('Ghostbusters', $this->user);

    $otherOwner = $this->createUser('Janine', 'janine@ghostbusters.com');
    $second = $this->createOrganization('Containment Unit', $otherOwner, [$this->user]);

    $outsider = $this->createOrganization('Hooli', $otherOwner);

    $this->user->refresh();

    $response = $this->getJson($this->endpoint);

    $response->assertOk();

    $returnedNames = collect($response->json('data'))->pluck('name');
    expect($returnedNames)->toContain($first->name);
    expect($returnedNames)->toContain($second->name);
    expect($returnedNames)->not()->toContain($outsider->name);
});

test('suspended users cannot access organizations endpoint', function (): void {
    $this->user->suspend();

    Sanctum::actingAs($this->user);

    $this->getJson($this->endpoint)->assertForbidden();
});

test('locked users cannot access organizations endpoint', function (): void {
    $this->user->lock();

    Sanctum::actingAs($this->user);

    $this->getJson($this->endpoint)->assertForbidden();
});
