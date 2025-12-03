<?php

namespace App\Environment\Resolvers;

use App\Environment\Models\Environment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class EnvironmentAncestryResolver
{
    public function get(Environment $env): Collection
    {
        return Cache::rememberForever($this->key($env->id), function () use ($env) {
            return $this->buildChain($env);
        });
    }

    public function bust(Environment $env): void
    {
        Cache::forget($this->key($env->id));
    }

    protected function buildChain(Environment $env): Collection
    {
        return collect([$env->withoutRelations()]);
    }

    protected function key(string $envId): string
    {
        return "env.ancestry_chain.{$envId}";
    }
}
