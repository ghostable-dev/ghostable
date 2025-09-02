<?php

use App\Project\Enums\ProjectNotification;
use App\Project\Livewire\ProjectNotificationsManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('project notifications can be toggled', function () {
    $user = $this->createUser('User', 'user@example.com');
    $org = $this->createOrganization('Org', $user);
    $project = $this->createProject('Website', $org);

    $this->actingAs($user);

    $component = Livewire::test(ProjectNotificationsManager::class, ['project' => $project]);

    expect($component->get('notificationOptions'))->toContain(ProjectNotification::PROJECT_SETTINGS_CHANGED);

    $component->call('toggle', ProjectNotification::PROJECT_SETTINGS_CHANGED->value);

    $project->refresh();
    expect($project->notifications->project_settings_changed)->toBeFalse();
});
