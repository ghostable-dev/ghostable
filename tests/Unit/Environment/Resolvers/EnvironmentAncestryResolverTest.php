<?php

use App\Environment\Models\Environment;
use App\Environment\Resolvers\EnvironmentAncestryResolver;
use App\Project\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function createHierarchy(): array
{
    $project = Project::factory()->create();
    $root = Environment::factory()->forProject($project)->create();
    $child = Environment::factory()->forProject($project)->basedOn($root)->create();
    $grand = Environment::factory()->forProject($project)->basedOn($child)->create();

    return [$root, $child, $grand];
}

it('resolves ancestry chains correctly', function () {
    [$root, $child, $grand] = createHierarchy();
    $resolver = new EnvironmentAncestryResolver;

    $rootChain = $resolver->get($root)->pluck('id')->all();
    $childChain = $resolver->get($child)->pluck('id')->all();
    $grandChain = $resolver->get($grand)->pluck('id')->all();

    expect($rootChain)->toBe([$root->id])
        ->and($childChain)->toBe([$root->id, $child->id])
        ->and($grandChain)->toBe([$root->id, $child->id, $grand->id]);
});

it('bust clears cached descendants', function () {
    [$root, $child, $grand] = createHierarchy();
    $resolver = new EnvironmentAncestryResolver;

    Cache::flush();
    $resolver->get($grand); // prime cache for root, child, grand

    expect(Cache::get("env.ancestry_chain.{$grand->id}"))->not()->toBeNull();

    $resolver->bust($child);

    expect(Cache::get("env.ancestry_chain.{$grand->id}"))->toBeNull();
});
