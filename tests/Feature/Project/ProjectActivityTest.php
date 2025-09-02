<?php

use App\Project\Livewire\ProjectActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('project activity can be viewed', function () {
    $user = $this->createUser('User', 'user@example.com');
    $org = $this->createOrganization('Org', $user);
    $project = $this->createProject('Website', $org);

    $this->actingAs($user);

    $component = Livewire::test(ProjectActivity::class, ['project' => $project])
        ->call('refreshActivities');

    expect($component->get('activities')->total())->toBeGreaterThanOrEqual(1);

    $component->assertViewIs('project.project-activity');
});
