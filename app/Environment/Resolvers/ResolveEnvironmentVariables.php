<?php

namespace App\Environment\Resolvers;

use App\Environment\Entities\ResolvedVariableData;
use App\Environment\Models\Environment;
use Illuminate\Support\Collection;

class ResolveEnvironmentVariables
{
    /**
     * Resolve the full set of variables for an environment, including
     * inherited values and metadata about their origin.
     *
     * @return Collection<int, ResolvedVariableData>
     */
    public function handle(Environment $env): Collection
    {
        // 1. Walk from root → base → current and collect all vars along the way
        $chain = $this->ancestryChain($env)->reverse(); // now current → root

        $resolved = collect(); // Final result keyed by `key`

        foreach ($chain as $envInChain) {
            $vars = $envInChain->variables()
                ->withLatestVersion()
                ->get();

            foreach ($vars as $var) {
                $key = $var->key;

                // If already set by a more derived env, skip (closest wins)
                if ($resolved->has($key)) {
                    continue;
                }

                $resolved->put($key, new ResolvedVariableData(
                    variable: $var,
                    inherited: $envInChain->id !== $env->id,
                    overridden: false, // set later if needed
                    overrides: false,
                    origin: $envInChain->name,
                ));
            }
        }

        // 2. Mark overrides (owned vars that override inherited ones)
        return $resolved->map(function (ResolvedVariableData $data) {
            if (! $data->inherited) {
                $data->overrides = true;
            }

            return $data;
        })->values();
    }

    /**
     * Build ancestry chain from root → current env.
     */
    protected function ancestryChain(Environment $env): Collection
    {
        $chain = collect();

        while ($env) {
            $chain->prepend($env); // Add to the beginning (root first)
            $env = $env->base;
        }

        return $chain;
    }
}
