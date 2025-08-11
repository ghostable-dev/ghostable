<?php

namespace App\Environment\Actions;

use App\Environment\Models\Environment;
use App\Environment\Resolvers\EnvironmentAncestryResolver;
use Illuminate\Support\Collection;

class ResolveEnvironmentVariables
{
    public static function handle(Environment $env): Collection
    {
        // 1. Walk from root → base → current and collect all vars along the way
        $chain = resolve(EnvironmentAncestryResolver::class)->get($env)->reverse(); // now current → root

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

                // Track inheritance and origin details
                $var->forceFill([
                    'inherited' => $envInChain->id !== $env->id,
                    'overridden' => false, // set later
                    'overrides' => false,
                    'origin' => $envInChain->name,
                ]);

                $resolved->put($key, $var);
            }
        }

        // 2. Mark overrides (owned vars that override inherited ones)
        return $resolved->map(function ($var) {
            if (! $var->inherited) {
                $var->overrides = true;
            }

            return $var;
        })->values();
    }

    /**
     * Build ancestry chain from root → current env.
     */
    protected static function ancestryChain(Environment $env): Collection
    {
        $chain = collect();

        while ($env) {
            $chain->prepend($env); // Add to the beginning (root first)
            $env = $env->base;
        }

        return $chain;
    }
}
