<?php

use App\Project\Enums\DeploymentProvider;
use App\Project\Enums\ProjectStackTag;
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
        ->set('stack.language', ProjectStackTag::LanguagePHP->value)
        ->set('stack.framework', ProjectStackTag::FrameworkLaravel->value)
        ->set('stack.platform', ProjectStackTag::PlatformLaravelForge->value)
        ->call('updateProject');

    $project = $project->fresh();
    expect($project->name)->toBe('New Name');
    expect($project->description)->toBe('Desc');
    expect($project->deployment_provider)->toBe(DeploymentProvider::LARAVEL_FORGE);
    expect($project->stack->language)->toBe(ProjectStackTag::LanguagePHP);
    expect($project->stack->framework)->toBe(ProjectStackTag::FrameworkLaravel);
    expect($project->stack->platform)->toBe(ProjectStackTag::PlatformLaravelForge);

    Livewire::test(ProjectGeneralSettings::class, ['project' => $project])
        ->call('deleteProject')
        ->assertRedirect(route('dashboard'));

    expect(Project::withTrashed()->find($project->id)->trashed())->toBeTrue();
});
