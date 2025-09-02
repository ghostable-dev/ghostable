<?php

use App\Environment\Models\Environment;
use App\Environment\Validation\Entities\CreateVariableRuleData;
use App\Environment\Validation\Enums\EnvironmentVariableRuleType;
use App\Project\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('converts string type to enum', function () {
    $project = Project::factory()->create();
    $environment = Environment::factory()->forProject($project)->create();

    $data = new CreateVariableRuleData(
        environment: $environment,
        key: 'FOO',
        isRequired: true,
        type: 'string',
        min: null,
        max: null,
        allowedValues: [],
    );

    expect($data->type)->toBe(EnvironmentVariableRuleType::STRING);
});
