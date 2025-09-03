<?php

use App\Environment\Models\Environment;
use App\Environment\Variable\Actions\ReinstateOverrideVariable;
use App\Environment\Variable\Models\EnvironmentVariable;
use App\Project\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('reinstates a suppressed override variable', function () {
    $project = Project::factory()->create();
    $env = Environment::factory()->forProject($project)->create();
    $var = new EnvironmentVariable([
        'key' => 'FOO',
        'is_override' => true,
        'is_deleted' => true,
        'is_commented' => false,
    ]);
    $var->environment()->associate($env);
    $var->value = 'bar';
    $var->save();

    app(ReinstateOverrideVariable::class)->handle($var);

    expect((bool) $var->fresh()->is_deleted)->toBeFalse();
    expect(Activity::forEvent('reinstated-override')->count())->toBe(1);
});

it('throws when variable is not suppressed', function () {
    $project = Project::factory()->create();
    $env = Environment::factory()->forProject($project)->create();
    $var = new EnvironmentVariable([
        'key' => 'FOO',
        'is_override' => true,
        'is_deleted' => false,
        'is_commented' => false,
    ]);
    $var->environment()->associate($env);
    $var->value = 'bar';
    $var->save();

    app(ReinstateOverrideVariable::class)->handle($var);
})->throws(\LogicException::class);
