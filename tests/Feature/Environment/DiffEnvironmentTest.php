<?php

use App\Environment\Actions\DiffEnvironment;
use App\Environment\Enums\EnvironmentType;
use App\Environment\Variable\Models\EnvironmentVariable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('detects added, updated, and removed variables', function () {
    $project = $this->createProject('proj', $this->createTeam('team', $this->createUser('u', 'u@example.com')));
    $env = $this->createEnvironment('Env', EnvironmentType::DEVELOPMENT, $project);

    EnvironmentVariable::factory()->forEnvironment($env)->create([
        'key' => 'FOO',
        'value' => 'bar',
    ]);

    EnvironmentVariable::factory()->forEnvironment($env)->create([
        'key' => 'BAZ',
        'value' => 'qux',
    ]);

    $result = app(DiffEnvironment::class)->handle($env, ['FOO=baz', 'NEW=1']);

    expect($result->added)->toHaveKey('NEW');
    expect($result->added['NEW']['value'])->toBe('1');

    expect($result->updated)->toHaveKey('FOO');
    expect($result->updated['FOO']['current']['value'])->toBe('bar');
    expect($result->updated['FOO']['incoming']['value'])->toBe('baz');

    expect($result->removed)->toHaveKey('BAZ');
    expect($result->removed['BAZ']['value'])->toBe('qux');
});
