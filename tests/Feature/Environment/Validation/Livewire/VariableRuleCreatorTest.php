<?php

use App\Environment\Enums\EnvironmentType;
use App\Environment\Validation\Enums\EnvironmentVariableRuleType;
use App\Environment\Validation\Livewire\VariableRuleCreator;
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

test('creator normalizes key and adds rule', function () {
    Livewire::test(VariableRuleCreator::class, ['environment' => $this->environment])
        ->call('launch')
        ->assertSet('showing', true)
        ->set('key', 'api key')
        ->assertSet('key', 'API_KEY')
        ->set('type', EnvironmentVariableRuleType::STRING)
        ->set('is_required', true)
        ->call('add')
        ->assertSet('key', '')
        ->assertSet('showing', false);

    expect($this->environment->rules()->where('key', 'API_KEY')->exists())->toBeTrue();
});
