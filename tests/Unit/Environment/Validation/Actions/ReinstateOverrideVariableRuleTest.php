<?php

use App\Environment\Models\Environment;
use App\Environment\Validation\Actions\ReinstateOverrideVariableRule;
use App\Environment\Validation\Enums\EnvironmentVariableRuleType;
use App\Project\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('reinstates a suppressed override rule', function () {
    $project = Project::factory()->create();
    $env = Environment::factory()->forProject($project)->create();
    $rule = $env->rules()->create([
        'key' => 'BAZ',
        'is_required' => true,
        'type' => EnvironmentVariableRuleType::STRING,
        'is_override' => true,
        'is_deleted' => true,
    ]);

    app(ReinstateOverrideVariableRule::class)->handle($rule);

    expect($rule->fresh()->is_deleted)->toBeFalse();
    expect(Activity::forEvent('reinstate-override')->count())->toBe(1);
});

it('throws when rule is not suppressed', function () {
    $project = Project::factory()->create();
    $env = Environment::factory()->forProject($project)->create();
    $rule = $env->rules()->create([
        'key' => 'BAZ',
        'is_required' => true,
        'type' => EnvironmentVariableRuleType::STRING,
        'is_override' => true,
        'is_deleted' => false,
    ]);

    app(ReinstateOverrideVariableRule::class)->handle($rule);
})->throws(\LogicException::class);
