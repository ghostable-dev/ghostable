<?php

use Laravel\Sanctum\Sanctum;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->ray = $this->createUser(name: 'Ray', email: 'ray@ghostbusters.com');
    $this->team = $this->createTeam(name: 'Ray’s Occult Books', owner: $this->ray);
    $this->website = $this->createProject(name: 'Website', team: $this->team);
    $this->createProject(name: 'Store', team: $this->team);
    $this->endpoint = "/api/teams/{$this->team->id}/projects";
});

test('unauthenticated users cannot fetch team projects', function () {
    $this->getJson($this->endpoint)
        ->assertUnauthorized();
});

test('returns only projects the team owns', function () {
    $peter = $this->createUser(name: 'Peter', email: 'peter@ghostbusters.com');
    $ghostbusters = $this->createTeam(name: 'Ghostbusters', owner: $peter);
    $hotline = $this->createProject(name: 'Website', team: $ghostbusters);
    Sanctum::actingAs($this->ray);
    $this->getJson($this->endpoint)->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonFragment(['id' =>$this->website->id])
        ->assertJsonMissing(['id' => $hotline->id]);
});

test('users cannot view team projects they do not belong to', function () {
    $peter = $this->createUser(name: 'Peter', email: 'peter@ghostbusters.com');
    Sanctum::actingAs($peter);
    $this->getJson($this->endpoint)->assertForbidden();
});

test('returns team projects in correct structure', function () {
    Sanctum::actingAs($this->ray);
    $this->getJson($this->endpoint)
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                [
                    'id',
                    'name',
                    'slug',
                    'team_id',
                    'environments',
                    'created_at',
                    'updated_at'
                ]
            ]
        ]);
});

