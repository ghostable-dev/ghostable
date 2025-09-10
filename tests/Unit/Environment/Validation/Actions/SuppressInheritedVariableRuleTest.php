<?php

use App\Account\Models\User;
use App\Environment\Models\Environment;
use App\Environment\Validation\Actions\SuppressInheritedVariableRule;
use App\Project\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('creates tombstone rule and logs suppression', function () {
    $project = Project::factory()->create();
    $env = Environment::factory()->forProject($project)->create();
    $user = User::factory()->create();

    app(SuppressInheritedVariableRule::class)->handle('FOO', $env, $user);

    $rule = $env->rules()->first();

    expect($rule->key)->toBe('FOO')
        ->and($rule->is_deleted)->toBeTrue()
        ->and($rule->is_override)->toBeFalse();

    expect(Activity::forEvent('suppress-inherited')->count())->toBe(1);
});
