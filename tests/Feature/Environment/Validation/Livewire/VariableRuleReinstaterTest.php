<?php

use App\Environment\Enums\EnvironmentType;
use App\Environment\Validation\Actions\CreateVariableRule;
use App\Environment\Validation\Entities\CreateVariableRuleData;
use App\Environment\Validation\Enums\EnvironmentVariableRuleType;
use App\Environment\Validation\Livewire\VariableRuleReinstater;
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
            key: 'SUPPRESSED',
            isRequired: true,
            type: EnvironmentVariableRuleType::STRING,
            min: null,
            max: null,
            allowedValues: [],
            description: null,
            isOverride: true,
            isDeleted: true,
            createdBy: $this->user,
        )
    );
});

test('reinstater reinstates suppressed override rule', function () {
    Livewire::test(VariableRuleReinstater::class)
        ->call('launchModal', $this->rule, $this->environment)
        ->assertSet('ruleId', $this->rule->id)
        ->call('reinstateRule')
        ->assertSet('ruleId', null);

    expect($this->rule->refresh()->is_deleted)->toBeFalse();
});
