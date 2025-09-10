<?php

use App\Environment\Enums\EnvironmentType;
use App\Environment\Validation\Actions\CreateVariableRule;
use App\Environment\Validation\Entities\CreateVariableRuleData;
use App\Environment\Validation\Enums\EnvironmentVariableRuleType;
use App\Environment\Validation\Livewire\VariableRuleDeleter;
use App\Environment\Validation\Models\EnvironmentVariableRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = $this->createUser('User', 'user@example.com');
    $this->organization = $this->createOrganization('Org', $this->user);
    $this->project = $this->createProject('Project', $this->organization);
    $this->environment = $this->createEnvironment('Env', EnvironmentType::DEVELOPMENT, $this->project);
    $this->actingAs($this->user);

    $this->rule = app(CreateVariableRule::class)->handle(
        new CreateVariableRuleData(
            environment: $this->environment,
            key: 'REMOVE_ME',
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

test('deleter removes rule', function () {
    Livewire::test(VariableRuleDeleter::class)
        ->call('launchModal', $this->rule, $this->environment)
        ->assertSet('ruleId', $this->rule->id)
        ->call('removeRule')
        ->assertSet('ruleId', null);

    expect(EnvironmentVariableRule::find($this->rule->id))->toBeNull();
});
