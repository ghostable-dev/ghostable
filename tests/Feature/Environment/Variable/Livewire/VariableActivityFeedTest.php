<?php

use App\Environment\Enums\EnvironmentType;
use App\Environment\Variable\Livewire\VariableActivityFeed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = $this->createUser('User', 'user@example.com');
    $this->organization = $this->createOrganization('Org', $this->user);
    $this->project = $this->createProject('Project', $this->organization);
    $this->environment = $this->createEnvironment('Env', EnvironmentType::DEVELOPMENT, $this->project);
    $this->createVariables($this->environment, 1, $this->user);
    $this->variable = $this->environment->variables()->first();
    $this->actingAs($this->user);
});

test('launch loads variable activity feed', function () {
    $component = Livewire::test(VariableActivityFeed::class)
        ->call('launch', $this->variable);

    expect($component->get('variable')->id)->toBe($this->variable->id);
    $activities = $component->get('activities');
    expect($activities)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    expect($component->get('showing'))->toBeTrue();
});
