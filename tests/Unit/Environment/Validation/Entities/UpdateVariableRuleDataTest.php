<?php

use App\Account\Models\User;
use App\Environment\Models\Environment;
use App\Environment\Validation\Entities\UpdateVariableRuleData;
use App\Environment\Validation\Enums\EnvironmentVariableRuleType;
use App\Project\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('converts string type to enum', function () {
    $project = Project::factory()->create();
    $environment = Environment::factory()->forProject($project)->create();

    $rule = $environment->rules()->create([
        'key' => 'FOO',
        'is_required' => true,
        'type' => EnvironmentVariableRuleType::STRING,
    ]);

    $user = User::factory()->create();

    $data = new UpdateVariableRuleData(
        rule: $rule,
        key: 'FOO',
        isRequired: true,
        type: 'integer',
        min: 1,
        max: 5,
        allowedValues: [],
        description: null,
        isOverride: false,
        isDeleted: false,
        updatedBy: $user,
    );

    expect($data->type)->toBe(EnvironmentVariableRuleType::INTEGER);
});
