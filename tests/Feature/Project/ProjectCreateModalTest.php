<?php

use App\Project\Enums\DeploymentProvider;
use App\Project\Enums\ProjectStackTag;
use App\Project\Livewire\ProjectCreateModal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('project can be created through modal', function () {
    $user = $this->createUser('User', 'user@example.com');
    $org = $this->createOrganization('Org', $user);

    $this->actingAs($user);

    Livewire::test(ProjectCreateModal::class)
        ->set('name', 'New Project')
        ->set('language', ProjectStackTag::LanguagePHP->value)
        ->set('framework', ProjectStackTag::FrameworkLaravel->value)
        ->set('platform', ProjectStackTag::PlatformLaravelForge->value)
        ->call('create')
        ->assertSet('name', '')
        ->assertSet('language', null)
        ->assertSet('framework', null)
        ->assertSet('platform', null);

    $project = $org->projects()->where('name', 'New Project')->first();

    expect($project)->not->toBeNull();
    expect($project->deployment_provider)->toBe(DeploymentProvider::LARAVEL_FORGE);
    expect($project->stack->language)->toBe(ProjectStackTag::LanguagePHP);
    expect($project->stack->framework)->toBe(ProjectStackTag::FrameworkLaravel);
    expect($project->stack->platform)->toBe(ProjectStackTag::PlatformLaravelForge);
});

test('stack selections are saved when provided', function () {
    $user = $this->createUser('User', 'user2@example.com');
    $org = $this->createOrganization('Org', $user);

    $this->actingAs($user);

    Livewire::test(ProjectCreateModal::class)
        ->set('name', 'Stacked Project')
        ->set('language', ProjectStackTag::LanguagePython->value)
        ->set('framework', ProjectStackTag::FrameworkFastAPI->value)
        ->set('platform', ProjectStackTag::PlatformAWS->value)
        ->call('create');

    $project = $org->projects()->where('name', 'Stacked Project')->first();

    expect($project->stack->language)->toBe(ProjectStackTag::LanguagePython);
    expect($project->stack->framework)->toBe(ProjectStackTag::FrameworkFastAPI);
    expect($project->stack->platform)->toBe(ProjectStackTag::PlatformAWS);
    expect($project->deployment_provider)->toBe(DeploymentProvider::OTHER);
});
