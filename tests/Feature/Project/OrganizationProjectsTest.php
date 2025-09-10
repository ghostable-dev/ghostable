<?php

use App\Project\Livewire\OrganizationProjects;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('organization projects are paginated', function () {
    $user = $this->createUser('User', 'user@example.com');
    $org = $this->createOrganization('Org', $user);
    $this->createProject('Website', $org);
    $this->createProject('Store', $org);

    $this->actingAs($user);

    $component = Livewire::test(OrganizationProjects::class);

    expect($component->get('projects')->total())->toBe(2);
    expect($component->get('organization')->is($org))->toBeTrue();

    $component->call('refreshProjects')
        ->assertViewIs('project.organization-projects');
});
