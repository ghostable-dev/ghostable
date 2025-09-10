<?php

use App\Account\Models\User;
use App\Environment\Models\Environment;
use App\Environment\Validation\Actions\LogVariableRuleActivity;
use App\Environment\Validation\Enums\EnvironmentVariableRuleType;
use App\Project\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('logs rule activity', function () {
    $project = Project::factory()->create();
    $env = Environment::factory()->forProject($project)->create();
    $rule = $env->rules()->create([
        'key' => 'FOO',
        'is_required' => true,
        'type' => EnvironmentVariableRuleType::STRING,
    ]);
    $user = User::factory()->create();

    app(LogVariableRuleActivity::class)->handle($rule, 'created', $user);

    $activity = Activity::where('log_name', 'env-rule')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->event)->toBe('created')
        ->and($activity->causer_id)->toBe($user->id)
        ->and($activity->description)->toBe("Added validation rule for \"FOO\" in \"{$env->name}\"")
        ->and($activity->getExtraProperty('key'))->toBe('FOO');
});
