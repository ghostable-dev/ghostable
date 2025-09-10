<?php

use App\Environment\Enums\EnvironmentType;
use App\Environment\Variable\Livewire\VariableEditor;
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

test('editor updates variable value', function () {
    $component = Livewire::test(VariableEditor::class)
        ->call('launchModal', $this->variable, $this->environment);

    $component->set('value', 'changed');
    $component->call('updateVariable');

    expect($this->variable->fresh()->value)->toBe('changed');
});
