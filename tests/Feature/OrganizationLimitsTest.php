<?php

use App\Organization\Actions\CreateOrganization;
use App\Project\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->owner = $this->createUser('Owner', 'owner@example.com');
})->skip();

test('personal organization default project limit enforced', function () {
    $organization = app(CreateOrganization::class)->handle('Personal', $this->owner, personal: true);
    Sanctum::actingAs($this->owner);
    $endpoint = "/api/v1/organizations/{$organization->id}/projects";
    $max = config('ghostable.personal_limits.projects');

    for ($i = 0; $i < $max; $i++) {
        $this->postJson($endpoint, ['name' => 'proj'.$i])->assertStatus(201);
    }

    $this->postJson($endpoint, ['name' => 'overflow'])
        ->assertStatus(422)
        ->assertJsonPath('error.fields.project_limit.0', 'Project limit reached for this organization.');
});

test('personal organization default environment limit enforced', function () {
    $organization = app(CreateOrganization::class)->handle('Personal', $this->owner, personal: true);
    $project = Project::factory()->forOrganization($organization)->create(['name' => 'App']);

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

test('personal organization project limit can be overridden', function () {
    $organization = app(CreateOrganization::class)->handle('Personal', $this->owner, personal: true);
    $organization->update(['limits' => ['projects' => 3]]);

    Sanctum::actingAs($this->owner);
    $endpoint = "/api/v1/organizations/{$organization->id}/projects";

    for ($i = 0; $i < 3; $i++) {
        $this->postJson($endpoint, ['name' => 'proj'.$i])->assertStatus(201);
    }

    $this->postJson($endpoint, ['name' => 'overflow'])
        ->assertStatus(422)
        ->assertJsonPath('error.fields.project_limit.0', 'Project limit reached for this organization.');
});

test('org organization defaults are unlimited', function () {
    $organization = app(CreateOrganization::class)->handle('Org', $this->owner, personal: false);
    Sanctum::actingAs($this->owner);
    $endpoint = "/api/v1/organizations/{$organization->id}/projects";

    for ($i = 0; $i < 5; $i++) {
        $this->postJson($endpoint, ['name' => 'proj'.$i])->assertStatus(201);
    }

    expect($organization->fresh()->projects()->count())->toBe(5);
});

test('org organization environment limit override enforced', function () {
    $organization = app(CreateOrganization::class)->handle('Org', $this->owner, personal: false);
    $organization->update(['limits' => ['environments_per_project' => 1]]);
    $project = Project::factory()->forOrganization($organization)->create(['name' => 'App']);

    Sanctum::actingAs($this->owner);
    $endpoint = "/api/v1/projects/{$project->id}/environments";

    $this->postJson($endpoint, ['name' => 'one', 'type' => 'local'])->assertStatus(201);
    $this->postJson($endpoint, ['name' => 'two', 'type' => 'local'])
        ->assertStatus(422)
        ->assertJsonPath('error.fields.environment_limit.0', 'Environment limit reached for this project.');
});
