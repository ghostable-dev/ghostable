<?php

use App\Account\Models\User;
use App\Environment\Models\Environment;
use App\Environment\Validation\Actions\UpdateVariableRule;
use App\Environment\Validation\Entities\UpdateVariableRuleData;
use App\Environment\Validation\Enums\EnvironmentVariableRuleType;
use App\Project\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('updates rule fields and logs activity', function () {
    $project = Project::factory()->create();
    $env = Environment::factory()->forProject($project)->create();
    $rule = $env->rules()->create([
        'key' => 'OLD',
        'is_required' => false,
        'type' => EnvironmentVariableRuleType::STRING,
        'min' => null,
        'max' => null,
        'allowed_values' => [],
        'description' => null,
        'is_override' => false,
        'is_deleted' => false,
    ]);
    $user = User::factory()->create();

    $data = new UpdateVariableRuleData(
        rule: $rule,
        key: 'NEW',
        isRequired: true,
        type: EnvironmentVariableRuleType::BOOLEAN,
        min: 1,
        max: 5,
        allowedValues: ['yes', 'no'],
        description: 'desc',
        isOverride: true,
        isDeleted: false,
        updatedBy: $user,
    );

    $updated = app(UpdateVariableRule::class)->handle($data);

    expect($updated->key)->toBe('NEW')
        ->and($updated->is_required)->toBeTrue()
        ->and($updated->type)->toBe(EnvironmentVariableRuleType::BOOLEAN)
        ->and($updated->min)->toBe(1)
        ->and($updated->max)->toBe(5)
        ->and($updated->allowed_values)->toBe(['yes', 'no'])
        ->and($updated->description)->toBe('desc')
        ->and($updated->is_override)->toBeTrue();

    expect(Activity::forEvent('updated')->count())->toBe(1);
});
