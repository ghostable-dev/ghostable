<?php

use App\Environment\Models\Environment;
use App\Environment\Rules\EnvironmentRules;
use App\Environment\Rules\UniqueEnvironmentName;
use App\Project\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('provides create rules', function () {
    $project = Project::factory()->create();

    $rules = EnvironmentRules::createRules($project);

    expect($rules)->toHaveKeys(['name', 'type']);
});

it('provides update rules', function () {
    $env = Environment::factory()->forProject(Project::factory()->create())->create();

    $rules = EnvironmentRules::updateRules($env);

    expect($rules)->toHaveKeys(['name', 'type', 'fileFormat']);
});

it('name rules include uniqueness', function () {
    $project = Project::factory()->create();

    $rules = EnvironmentRules::nameRules($project);

    expect($rules)->toContain('required')
        ->and($rules)->toContain('string')
        ->and($rules)->toContain('max:100')
        ->and($rules)->toContain('regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
        ->and(last($rules))->toBeInstanceOf(UniqueEnvironmentName::class);
});

it('type rules require a value', function () {
    $rules = EnvironmentRules::typeRules();

    expect($rules[0])->toBe('required');
});

it('format rules respect required flag', function () {
    $required = EnvironmentRules::formatRules();
    $optional = EnvironmentRules::formatRules(false);

    expect($required[0])->toBe('required')
        ->and($optional[0])->toBe('sometimes');
});
