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

        foreach ($this->descendantsOf($env) as $child) {
            Cache::forget($this->key($child->id));
        }
    }

    protected function buildChain(Environment $env): Collection
    {
        $chain = collect();
        $cursor = $env->withoutRelations();

        while ($cursor) {
            $chain->prepend($cursor);
            $cursor = $cursor->base;
        }

        return $chain->values();
    }

    protected function descendantsOf(Environment $env): Collection
    {
        $descendants = collect();
        $queue = $env->derived()->get(); // empty if none

        while ($queue->isNotEmpty()) {
            /** @var Environment $node */
            $node = $queue->shift();
            $descendants->push($node);

            // enqueue this node’s derived
            $queue = $queue->concat($node->derived()->get());
        }

        return $descendants;
    }

    protected function key(string $envId): string
    {
        return "env.ancestry_chain.{$envId}";
    }
}