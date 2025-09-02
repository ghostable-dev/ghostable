<?php

use App\Environment\Actions\ResolveEnvironmentVariables;
use App\Environment\Models\Environment;
use App\Environment\Resolvers\EnvironmentAncestryResolver;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Tests\TestCase;

uses(TestCase::class);

class ResolveVar
{
    public function __construct(private array $attributes) {}

    public function __get($key)
    {
        return $this->attributes[$key];
    }

    public function forceFill(array $attrs)
    {
        foreach ($attrs as $k => $v) {
            $this->attributes[$k] = $v;
        }
    }
}

it('merges variables and respects overrides', function () {
    $root = \Mockery::mock(Environment::class)->makePartial();
    $root->id = 'root';
    $root->name = 'root';
    $rootRelation = \Mockery::mock(HasMany::class);
    $rootRelation->shouldReceive('withLatestVersion')->andReturnSelf();
    $rootRelation->shouldReceive('get')->andReturn(collect([
        new ResolveVar(['key' => 'FOO', 'value' => 'root']),
        new ResolveVar(['key' => 'BAZ', 'value' => 'baz']),
    ]));
    $root->shouldReceive('variables')->andReturn($rootRelation);

    $child = \Mockery::mock(Environment::class)->makePartial();
    $child->id = 'child';
    $child->name = 'child';
    $child->base = $root;
    $childRelation = \Mockery::mock(HasMany::class);
    $childRelation->shouldReceive('withLatestVersion')->andReturnSelf();
    $childRelation->shouldReceive('get')->andReturn(collect([
        new ResolveVar(['key' => 'FOO', 'value' => 'child']),
        new ResolveVar(['key' => 'BAR', 'value' => 'bar']),
    ]));
    $child->shouldReceive('variables')->andReturn($childRelation);

    app()->instance(EnvironmentAncestryResolver::class, new class($root, $child)
    {
        public function __construct(private Environment $root, private Environment $child) {}

        public function get($env): Collection
        {
            return collect([$this->root, $this->child]);
        }
    });

    $resolved = ResolveEnvironmentVariables::handle($child);

    $keys = $resolved->map(fn ($v) => $v->key);
    expect($resolved)->toHaveCount(3)
        ->and($keys)->toContain('FOO', 'BAR', 'BAZ');
});
