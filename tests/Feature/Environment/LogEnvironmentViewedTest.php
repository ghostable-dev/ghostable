<?php

use App\Account\Models\User;
use App\Core\Models\Activity;
use App\Environment\Actions\LogEnvironmentViewed;
use App\Environment\Models\Environment;
use App\Project\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('logs activity when viewing environment variables', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $env = Environment::factory()->forProject($project)->create();

    resolve(LogEnvironmentViewed::class)->handle($env, $user);

    expect(Activity::query()->where('event', 'viewed')->count())->toBe(1);
});

it('does not log duplicate activity within cooldown', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $env = Environment::factory()->forProject($project)->create();

    resolve(LogEnvironmentViewed::class)->handle($env, $user);
    resolve(LogEnvironmentViewed::class)->handle($env, $user);

    expect(Activity::query()->where('event', 'viewed')->count())->toBe(1);
});
