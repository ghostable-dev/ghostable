<?php

use App\Environment\Enums\EnvironmentType;
use App\Project\Livewire\ProjectEnvironmentsManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('environment can be created', function () {
    $user = $this->createUser('User', 'user@example.com');
    $org = $this->createOrganization('Org', $user);
    $project = $this->createProject('Website', $org);

    $this->actingAs($user);

    Livewire::test(ProjectEnvironmentsManager::class, ['project' => $project])
        ->set('name', 'staging')
        ->set('type', EnvironmentType::STAGING)
        ->call('createEnvironment');

    expect($project->environments()->where('name', 'staging')->exists())->toBeTrue();
});
