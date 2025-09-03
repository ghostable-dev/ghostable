<?php

use App\Environment\Models\Environment;
use App\Environment\Variable\Actions\SuppressOverrideVariable;
use App\Environment\Variable\Models\EnvironmentVariable;
use App\Project\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('marks override variable as deleted and logs', function () {
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

    app(SuppressOverrideVariable::class)->handle($var);

    expect((bool) $var->fresh()->is_deleted)->toBeTrue();
    expect(Activity::forEvent('suppress-override')->count())->toBe(1);
});
