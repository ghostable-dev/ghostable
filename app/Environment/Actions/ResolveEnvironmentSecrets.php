<?php

namespace App\Environment\Actions;

use App\Environment\Models\Environment;
use App\Environment\Models\EnvironmentSecret;
use App\Environment\Resolvers\EnvironmentAncestryResolver;
use Illuminate\Support\Collection;

class ResolveEnvironmentSecrets
{
    /**
     * Resolve secrets across ancestry with "closest env wins".
     *
     * @return Collection<EnvironmentSecret>
     */
    public static function handle(Environment $env, array $only = []): Collection
    {
        // current → … → root (closest first so we can short-circuit)
        $chain = resolve(EnvironmentAncestryResolver::class)->get($env)->reverse();

        $resolved = collect(); // keyed by secret name

        foreach ($chain as $envInChain) {
            $query = $envInChain->envSecrets()
                ->with('latestVersion');

            if (! empty($only)) {
                $query->whereIn('name', $only);
            }

            $secrets = $query->get();

            foreach ($secrets as $secret) {
                $key = $secret->name;

                // If already taken by a more derived env, skip
                if ($resolved->has($key)) {
                    continue;
                }

                // Annotate the model instance without persisting
                $secret->forceFill([
                    'inherited' => $envInChain->id !== $env->id,
                    'overridden' => false, // older ancestors that got shadowed aren't returned
                    'overrides' => false, // set below for locally-owned entries
                    'origin' => $envInChain->name,
                ]);

                $resolved->put($key, $secret);
            }
        }

        // Mark locally-owned entries as "overrides"
        return $resolved->map(function ($secret) {
            if (! $secret->inherited) {
                $secret->overrides = true;
            }

            return $secret;
        })->values();
    }
}
