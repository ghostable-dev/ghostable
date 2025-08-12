<?php

namespace App\Environment\Actions;

use App\Environment\Models\Environment;
use App\Environment\Variable\Models\EnvironmentVariable;
use Illuminate\Support\Collection;

class UpdateBaseEnvironment
{
    public function handle(Environment $environment, ?Environment $base): void
    {
        $environment->base()->associate($base);

        // Gather the ancestry chain using the newly associated base so we can
        // determine which variables exist in ancestor environments.
        $chain = collect(BuildEnvironmentAncestryChain::handle($environment));

        // We only care about the ancestors (exclude the current environment)
        // and we want to inspect them from the closest ancestor outward.
        $ancestors = $chain->slice(0, -1)->reverse();

        // Preload the current environment's variables so we can mutate them.
        $environment->load('variables');

        /** @var EnvironmentVariable $var */
        foreach ($environment->variables as $var) {
            // Find the nearest ancestor variable (if any) for the same key.
            $ancestorVar = $this->findNearestAncestorVariable($ancestors, $var->key);

            if ($var->is_deleted) {
                // A tombstone that no longer suppresses anything can be dropped.
                if (! $ancestorVar) {
                    $var->delete();
                }

                continue;
            }

            if ($var->is_override) {
                // If no ancestor provides this key anymore, it's no longer an override.
                if (! $ancestorVar) {
                    $var->is_override = false;
                    $var->save();
                }

                continue;
            }

            // Regular variable. If an ancestor now provides this key, we either
            // remove the local copy (identical value) or mark it as an override
            // so its differing value takes precedence.
            if ($ancestorVar) {
                if ($ancestorVar->value === $var->value) {
                    $var->delete();
                } else {
                    $var->is_override = true;
                    $var->save();
                }
            }
        }

        $environment->save();
    }

    /**
     * Locate the closest ancestor variable for the given key.
     *
     * @param  Collection<int, Environment>  $ancestors
     */
    protected function findNearestAncestorVariable(Collection $ancestors, string $key): ?EnvironmentVariable
    {
        foreach ($ancestors as $ancestor) {
            /** @var EnvironmentVariable|null $candidate */
            $candidate = $ancestor->variables()
                ->where('key', $key)
                ->where('is_deleted', false)
                ->first();

            if ($candidate) {
                return $candidate;
            }
        }

        return null;
    }
}
