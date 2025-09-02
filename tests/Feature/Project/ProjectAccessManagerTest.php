<?php

use App\Project\Livewire\ProjectAccessManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('project access restriction can be updated and canceled', function () {
    $user = $this->createUser('User', 'user@example.com');
    $org = $this->createOrganization('Org', $user);
    $project = $this->createProject('Website', $org);
    $project->update(['is_restricted' => false]);

    $this->actingAs($user);

    $component = Livewire::test(ProjectAccessManager::class, ['project' => $project])
        ->assertViewIs('project.project-access-manager');

    $component->set('is_restricted', true)
        ->call('cancelIsRestrictedChange');

    expect($component->get('is_restricted'))->toBeFalse();

    $component->set('is_restricted', true)
        ->call('updateIsRestricted');
});
