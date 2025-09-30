<?php

namespace App\Environment\Actions;

use App\Environment\Models\Environment;
use App\Environment\Resolvers\ResolveEnvironmentVariables;

class SumResolvedLineBytes
{
    public function __construct(
        protected ResolveEnvironmentVariables $resolver,
    ) {}

    public function handle(Environment $env, bool $onlyVaporSecrets = true): int
    {
        $resolved = $this->resolver->handle($env); // Collection<ResolvedVariableData>

        return $resolved
            ->filter(function ($data) {
                $var = $data->variable;

                // if ($onlyVaporSecrets) {
                //     if (! $var->vapor_secret) {
                //         return false;
                //     }
                // }

                // Skip null values (treat as empty string if you prefer to count them)
                return $var->value !== null;
            })->sum(fn ($data) => $data->variable->line_bytes);
    }
}
