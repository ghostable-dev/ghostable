<?php

use App\Team\Enums\TeamRole;
use Laravel\Sanctum\Sanctum;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->ray = $this->createUser(name: 'Ray', email: 'ray@ghostbusters.com');
    $this->team = $this->createTeam(name: 'Ray’s Occult Books', owner: $this->ray);
    $this->endpoint = "/api/teams/{$this->team->id}/projects";
});

test('unauthenticated users cannot create projects', function () {
    $this->getJson($this->endpoint)->assertUnauthorized();
});

test('persists a new project record and returns JSON shape', function () {
    Sanctum::actingAs($this->ray);
    $payload = ['name' => 'Website'];
    $this->postJson($this->endpoint, $payload)
         ->assertStatus(201)
         ->assertJsonStructure([
             'data' => [
                'id',
                'name',
                'slug',
                'team_id',
                'environments',
                'created_at',
                'updated_at'
             ],
         ]);
    $project = $this->team->fresh()->projects()->where($payload)->first();
    $this->assertNotNull($project);
});

describe('authorization', function () {
    beforeEach(function () {
        $this->zuul = $this->createUser(name: 'Zuul', email: 'zuul@gozers-minions.com');
    });
    
    test('forbids non-members from creating', function () {
        Sanctum::actingAs($this->zuul);
        $this->postJson($this->endpoint, ['name' => 'Website'])->assertForbidden();
    });

    test('forbids members without permission from creating', function () {
        $peter = $this->createUser(name: 'Peter', email: 'perter@ghostbusters.com');
        $peter->teamMembership()->assignToTeam(team: $this->team, role: TeamRole::DEVELOPER_READ_ONLY);
        Sanctum::actingAs($peter);
        $this->postJson($this->endpoint, ['name' => 'Website'])->assertForbidden();
    }); 
});