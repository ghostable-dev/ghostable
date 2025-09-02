<?php

use App\Environment\Enums\EnvironmentType;
use App\Environment\Variable\Livewire\VariableReinstater;
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
    $this->variable->update(['is_deleted' => 1]);
    $this->actingAs($this->user);
});

test('reinstater restores suppressed variable', function () {
    Livewire::test(VariableReinstater::class)
        ->call('launchModal', $this->variable, $this->environment)
        ->call('reinstateVariable');

    expect($this->variable->fresh()->trashed())->toBeTrue();
});
