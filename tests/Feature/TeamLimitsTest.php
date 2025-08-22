<?php

use App\Project\Models\Project;
use App\Team\Actions\CreateTeam;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->owner = $this->createUser('Owner', 'owner@example.com');
});

test('personal team default project limit enforced', function () {
    $team = app(CreateTeam::class)->handle('Personal', $this->owner, personal: true);
    Sanctum::actingAs($this->owner);
    $endpoint = "/api/v1/teams/{$team->id}/projects";
    $max = config('ghostable.personal_limits.projects');

    for ($i = 0; $i < $max; $i++) {
        $this->postJson($endpoint, ['name' => 'proj'.$i])->assertStatus(201);
    }

    $this->postJson($endpoint, ['name' => 'overflow'])
        ->assertStatus(422)
        ->assertJsonPath('error.fields.project_limit.0', 'Project limit reached for this team.');
});

test('personal team default environment limit enforced', function () {
    $team = app(CreateTeam::class)->handle('Personal', $this->owner, personal: true);
    $project = Project::factory()->forTeam($team)->create(['name' => 'App']);

    Sanctum::actingAs($this->owner);
    $endpoint = "/api/v1/projects/{$project->id}/environments";
    $max = config('ghostable.personal_limits.environments_per_project');

    for ($i = 0; $i < $max; $i++) {
        $this->postJson($endpoint, ['name' => 'env'.$i, 'type' => 'local'])->assertStatus(201);
    }

    $this->postJson($endpoint, ['name' => 'envx', 'type' => 'local'])
        ->assertStatus(422)
        ->assertJsonPath('error.fields.environment_limit.0', 'Environment limit reached for this project.');
});

test('personal team project limit can be overridden', function () {
    $team = app(CreateTeam::class)->handle('Personal', $this->owner, personal: true);
    $team->update(['limits' => ['projects' => 3]]);

    Sanctum::actingAs($this->owner);
    $endpoint = "/api/v1/teams/{$team->id}/projects";

    for ($i = 0; $i < 3; $i++) {
        $this->postJson($endpoint, ['name' => 'proj'.$i])->assertStatus(201);
    }

    $this->postJson($endpoint, ['name' => 'overflow'])
        ->assertStatus(422)
        ->assertJsonPath('error.fields.project_limit.0', 'Project limit reached for this team.');
});

test('org team defaults are unlimited', function () {
    $team = app(CreateTeam::class)->handle('Org', $this->owner, personal: false);
    Sanctum::actingAs($this->owner);
    $endpoint = "/api/v1/teams/{$team->id}/projects";

    for ($i = 0; $i < 5; $i++) {
        $this->postJson($endpoint, ['name' => 'proj'.$i])->assertStatus(201);
    }

    expect($team->fresh()->projects()->count())->toBe(5);
});

test('org team environment limit override enforced', function () {
    $team = app(CreateTeam::class)->handle('Org', $this->owner, personal: false);
    $team->update(['limits' => ['environments_per_project' => 1]]);
    $project = Project::factory()->forTeam($team)->create(['name' => 'App']);

    Sanctum::actingAs($this->owner);
    $endpoint = "/api/v1/projects/{$project->id}/environments";

    $this->postJson($endpoint, ['name' => 'one', 'type' => 'local'])->assertStatus(201);
    $this->postJson($endpoint, ['name' => 'two', 'type' => 'local'])
        ->assertStatus(422)
        ->assertJsonPath('error.fields.environment_limit.0', 'Environment limit reached for this project.');
});
