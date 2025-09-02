<?php

use App\Project\Livewire\ProjectGeneralSettings;
use App\Project\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('project settings can be updated and deleted', function () {
    $user = $this->createUser('User', 'user@example.com');
    $org = $this->createOrganization('Org', $user);
    $project = $this->createProject('Website', $org);

    $this->actingAs($user);

    Livewire::test(ProjectGeneralSettings::class, ['project' => $project])
        ->set('name', 'New Name')
        ->set('description', 'Desc')
        ->call('updateProject');

    $project = $project->fresh();
    expect($project->name)->toBe('New Name');
    expect($project->description)->toBe('Desc');

    Livewire::test(ProjectGeneralSettings::class, ['project' => $project])
        ->call('deleteProject')
        ->assertRedirect(route('dashboard'));

    expect(Project::withTrashed()->find($project->id)->trashed())->toBeTrue();
});
