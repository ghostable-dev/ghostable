<?php

use App\Environment\Enums\EnvironmentType;
use App\Environment\Variable\Livewire\VariableManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = $this->createUser('User', 'user@example.com');
    $this->organization = $this->createOrganization('Org', $this->user);
    $this->project = $this->createProject('Project', $this->organization);
    $this->environment = $this->createEnvironment('Env', EnvironmentType::DEVELOPMENT, $this->project);
    $this->createVariables($this->environment, 2, $this->user);
    $this->variable = $this->environment->variables()->first();
    $this->actingAs($this->user);
});

test('manager sorts and toggles variables', function () {
    $component = Livewire::test(VariableManager::class, ['environment' => $this->environment]);

    $component->call('sort', 'key');
    expect($component->get('sortDirection'))->toBe('desc');

    $component->call('sort', 'last_updated_at');
    expect($component->get('sortBy'))->toBe('last_updated_at');

    $component->call('toggleSecret', $this->variable);
    expect($component->get('showing')[$this->variable->id])->toBeTrue();

    $component->call('editVariable', $this->variable);
    $component->call('removeVariable', $this->variable);
    $component->call('reinstateVariable', $this->variable);
});
