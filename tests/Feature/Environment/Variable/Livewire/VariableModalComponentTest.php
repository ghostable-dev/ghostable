<?php

use App\Environment\Enums\EnvironmentType;
use App\Environment\Variable\Livewire\VariableModalComponent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

class DummyModal extends VariableModalComponent
{
    public const LAUNCH = 'dummy:launch';

    public function render()
    {
        return '<div></div>';
    }

    protected function resetValues(): void
    {
        // no-op for testing
    }
}

beforeEach(function () {
    $this->user = $this->createUser('User', 'user@example.com');
    $this->organization = $this->createOrganization('Org', $this->user);
    $this->project = $this->createProject('Project', $this->organization);
    $this->environment = $this->createEnvironment('Env', EnvironmentType::DEVELOPMENT, $this->project);
    $this->createVariables($this->environment, 1, $this->user);
    $this->variable = $this->environment->variables()->first();
    $this->actingAs($this->user);
});

test('modal launches and resets', function () {
    $component = Livewire::test(DummyModal::class)
        ->call('launchModal', $this->variable, $this->environment);

    expect($component->get('variable')->id)->toBe($this->variable->id);
    expect($component->get('targetEnvironment')->id)->toBe($this->environment->id);
    expect($component->get('isLocalToTarget'))->toBeTrue();
    expect($component->get('isOverride'))->toBeFalse();
});
