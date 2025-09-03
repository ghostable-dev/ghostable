<?php

use App\Environment\Enums\EnvironmentType;
use App\Environment\Validation\Actions\CreateVariableRule;
use App\Environment\Validation\Entities\CreateVariableRuleData;
use App\Environment\Validation\Enums\EnvironmentVariableRuleType;
use App\Environment\Validation\Livewire\VariableRuleEditor;
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
            key: 'EDIT_ME',
            isRequired: true,
            type: EnvironmentVariableRuleType::STRING,
            min: null,
            max: null,
            allowedValues: [],
            description: 'Before',
            isOverride: false,
            isDeleted: false,
            createdBy: $this->user,
        )
    );
});

test('editor updates rule description', function () {
    Livewire::test(VariableRuleEditor::class)
        ->call('launchEditorModal', $this->rule, $this->environment)
        ->assertSet('ruleId', $this->rule->id)
        ->set('description', 'After')
        ->call('update')
        ->assertSet('ruleId', null);

    expect($this->rule->refresh()->description)->toBe('After');
});
