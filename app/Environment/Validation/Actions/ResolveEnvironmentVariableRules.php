<?php

namespace App\Environment\Validation\Actions;

use App\Environment\Models\Environment;
use App\Environment\Resolvers\EnvironmentAncestryResolver;
use App\Environment\Validation\Models\EnvironmentVariableRule;
use Illuminate\Support\Collection;

class ResolveEnvironmentVariableRules
{
    public function __construct(
        protected EnvironmentAncestryResolver $ancestryResolver
    ) {}

    /**
     * Resolve all validation rules for the given environment,
     * honoring inheritance, overrides and tombstones.
     */
    public function handle(Environment $environment): Collection
    {
        $chain = $this->ancestryResolver->get($environment)->reverse();

        $resolved = collect();

        foreach ($chain as $envInChain) {
            $rules = $envInChain->rules()->get();

            foreach ($rules as $rule) {
                $key = $rule->key;

                if ($resolved->has($key)) {
                    continue;
                }

                $rule->forceFill([
                    'inherited' => $envInChain->id !== $environment->id,
                    'overridden' => false,
                    'overrides' => false,
                    'origin' => $envInChain->name,
                ]);

                $resolved->put($key, $rule);
            }
        }

        return $resolved
            ->map(function (EnvironmentVariableRule $rule) {
                if (! $rule->inherited) {
                    $rule->overrides = true;
                }

                return $rule;
            })
            ->values();
    }
}
