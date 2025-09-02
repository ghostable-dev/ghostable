<?php

use App\Environment\Models\Environment;
use App\Project\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('determines if rule belongs to environment', function () {
    $project = Project::factory()->create();
    $envA = Environment::factory()->forProject($project)->create();
    $envB = Environment::factory()->forProject($project)->create();

    $rule = $envA->rules()->create([
        'key' => 'FOO',
        'is_required' => true,
        'type' => 'string',
    ]);

    expect($rule->belongsToEnvironment($envA))->toBeTrue()
        ->and($rule->belongsToEnvironment($envB))->toBeFalse();
});
