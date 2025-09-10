<?php

use App\Account\Models\User;
use App\Environment\Models\Environment;
use App\Environment\Validation\Actions\ReinstateInheritedVariableRule;
use App\Environment\Validation\Enums\EnvironmentVariableRuleType;
use App\Project\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('reinstates inherited rule by deleting tombstone', function () {
    $project = Project::factory()->create();
    $env = Environment::factory()->forProject($project)->create();
    $rule = $env->rules()->create([
        'key' => 'BAR',
        'is_required' => true,
        'type' => EnvironmentVariableRuleType::STRING,
        'is_override' => false,
        'is_deleted' => true,
    ]);
    $user = User::factory()->create();

    app(ReinstateInheritedVariableRule::class)->handle($rule, $user);

    expect($env->rules()->find($rule->id))->toBeNull();

    expect(Activity::forEvent('reinstate-inherited')->count())->toBe(1);
});

it('throws when rule is active', function () {
    $project = Project::factory()->create();
    $env = Environment::factory()->forProject($project)->create();
    $rule = $env->rules()->create([
        'key' => 'BAR',
        'is_required' => true,
        'type' => EnvironmentVariableRuleType::STRING,
        'is_override' => false,
        'is_deleted' => false,
    ]);

    app(ReinstateInheritedVariableRule::class)->handle($rule);
})->throws(\LogicException::class);
