<?php

use App\Environment\Enums\EnvironmentType;
use App\Environment\Validation\Actions\CreateVariableRule;
use App\Environment\Validation\Entities\CreateVariableRuleData;
use App\Environment\Validation\Enums\EnvironmentVariableRuleType;
use App\Environment\Validation\Livewire\ValidationManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = $this->createUser('User', 'user@example.com');
    $this->organization = $this->createOrganization('Org', $this->user);
    $this->project = $this->createProject('Project', $this->organization);
    $this->environment = $this->createEnvironment('Env', EnvironmentType::DEVELOPMENT, $this->project);
    $this->actingAs($this->user);

    $this->ruleB = app(CreateVariableRule::class)->handle(
        new CreateVariableRuleData(
            environment: $this->environment,
            key: 'B_KEY',
            isRequired: true,
            type: EnvironmentVariableRuleType::STRING,
            min: null,
            max: null,
            allowedValues: [],
            description: null,
            isOverride: false,
            isDeleted: false,
            createdBy: $this->user,
        )
    );

    $this->ruleA = app(CreateVariableRule::class)->handle(
        new CreateVariableRuleData(
            environment: $this->environment,
            key: 'A_KEY',
            isRequired: true,
            type: EnvironmentVariableRuleType::STRING,
            min: null,
            max: null,
            allowedValues: [],
            description: null,
            isOverride: false,
            isDeleted: false,
            createdBy: $this->user,
        )
    );
});

test('manager sorts rules and dispatches events', function () {
    $component = Livewire::test(ValidationManager::class, ['environment' => $this->environment]);

    expect($component->get('rules')->pluck('key')->all())->toBe(['A_KEY', 'B_KEY']);

    $component->call('sort', 'key');

    expect($component->get('sortDirection'))->toBe('desc')
        ->and($component->get('rules')->pluck('key')->all())->toBe(['B_KEY', 'A_KEY']);

    $component->call('sort', 'updated_at');

    expect($component->get('sortBy'))->toBe('updated_at');

    expect($component->get('canEditVariables'))->toBeTrue();

    $component->call('launchCreateRuleModal');
    $component->call('editRule', $this->ruleA);
    $component->call('removeRule', $this->ruleA);
    $component->call('reinstateRule', $this->ruleA);
    $component->call('refreshRules');
});
