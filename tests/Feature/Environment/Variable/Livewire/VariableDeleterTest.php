<?php

use App\Environment\Enums\EnvironmentType;
use App\Environment\Variable\Livewire\VariableDeleter;
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

test('deleter removes variable', function () {
    $component = Livewire::test(VariableDeleter::class)
        ->call('launchModal', $this->variable, $this->environment);

    $component->call('removeVariable');

    expect($this->variable->fresh()->trashed())->toBeTrue();
});
