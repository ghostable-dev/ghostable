<?php

use App\Environment\Enums\EnvironmentType;
use Laravel\Sanctum\Sanctum;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('unauthenticated users create environments', function () {
    $this->postJson("/api/projects/123/environments")
        ->assertUnauthorized();
});

test('users can create environments', function () {
    $ray = $this->createUser(name: 'Ray', email: 'ray@ghostbusters.com');
    $team = $this->createTeam(name: 'Ray’s Occult Books', owner: $ray);
    $project = $this->createProject(name: 'Website', team: $team);
    Sanctum::actingAs($ray);
    
    $response = $this->postJson("/api/projects/{$project->id}/environments", [
        'name' => 'Production',
        'type' => EnvironmentType::PRODUCTION->value
    ])->assertStatus(201);

    //$env = $project->fresh()->enviroments()->where($payload)->first();
    //$this->assertNotNull($invite);
});

// test('returns all environment types in the correct format', function () {
//     $ray = $this->createUser(name: 'Ray', email: 'ray@ghostbusters.com');
//     Sanctum::actingAs($ray);

//     $expected = collect(EnvironmentType::cases())
//         ->map(fn (EnvironmentType $type) => [
//             'value'         => $type->value,
//             'label'       => $type->label(),
//         ]);

//     $response = $this->getJson('/api/environment-types');

//     $response->assertOk()
//         ->assertJsonCount($expected->count(), 'data')
//         ->assertExactJson(['data' => $expected->all()]);
// });