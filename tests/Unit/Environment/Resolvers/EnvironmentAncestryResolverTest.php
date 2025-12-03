<?php

use App\Environment\Resolvers\EnvironmentAncestryResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('resolves ancestry chains correctly', function () {
    $env = \App\Environment\Models\Environment::factory()
        ->forProject(\App\Project\Models\Project::factory()->create())
        ->create();
    $resolver = new EnvironmentAncestryResolver;

    $chain = $resolver->get($env)->pluck('id')->all();

    expect($chain)->toBe([$env->id]);
});

it('bust clears cached descendants', function () {
    $env = \App\Environment\Models\Environment::factory()
        ->forProject(\App\Project\Models\Project::factory()->create())
        ->create();
    $resolver = new EnvironmentAncestryResolver;

    Cache::flush();
    $resolver->get($env); // prime cache

    expect(Cache::get("env.ancestry_chain.{$env->id}"))->not()->toBeNull();

    $resolver->bust($env);

    expect(Cache::get("env.ancestry_chain.{$env->id}"))->toBeNull();
});
