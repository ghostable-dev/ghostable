<?php

use App\Environment\Models\Environment;
use App\Environment\Validation\Actions\SuppressOverrideVariableRule;
use App\Environment\Validation\Enums\EnvironmentVariableRuleType;
use App\Project\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('marks override rule as deleted and logs', function () {
    $project = Project::factory()->create();
    $env = Environment::factory()->forProject($project)->create();
    $rule = $env->rules()->create([
        'key' => 'QUX',
        'is_required' => true,
        'type' => EnvironmentVariableRuleType::STRING,
        'is_override' => true,
        'is_deleted' => false,
    ]);

    app(SuppressOverrideVariableRule::class)->handle($rule);

    expect($rule->fresh()->is_deleted)->toBeTrue();
    expect(Activity::forEvent('suppress-override')->count())->toBe(1);
});
