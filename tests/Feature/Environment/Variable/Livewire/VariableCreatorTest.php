<?php

use App\Environment\Enums\EnvironmentType;
use App\Environment\Variable\Livewire\VariableCreator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = $this->createUser('User', 'user@example.com');
    $this->organization = $this->createOrganization('Org', $this->user);
    $this->project = $this->createProject('Project', $this->organization);
    $this->environment = $this->createEnvironment('Env', EnvironmentType::DEVELOPMENT, $this->project);
    $this->actingAs($this->user);
});

test('creator adds variable', function () {
    Livewire::test(VariableCreator::class, ['environment' => $this->environment])
        ->set('key', 'NEW_KEY')
        ->set('value', 'VALUE')
        ->call('addVariable')
        ->assertSet('key', '')
        ->assertSet('value', '');

    expect($this->environment->variables()->where('key', 'NEW_KEY')->exists())->toBeTrue();
});
