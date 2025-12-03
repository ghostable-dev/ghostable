<?php

namespace App\Environment\Actions;

use App\Environment\Models\Environment;

class SumResolvedLineBytes
{
    public function __construct(
        protected ResolveEnvironmentSecrets $resolver,
    ) {}

    public function handle(Environment $env, bool $onlyVaporSecrets = true): int
    {
        $resolved = $this->resolver->handle($env); // Collection<EnvironmentSecret>

        return $resolved
            ->filter(function ($data) use ($onlyVaporSecrets) {
                $secret = $data;

                if ($onlyVaporSecrets) {
                    if (! $secret->is_vapor_secret) {
                        return false;
                    }
                }

                // Skip null values (treat as empty string if you prefer to count them)
                return $secret->ciphertext !== null;
            })->sum(fn ($secret) => $secret->line_bytes);
    }
}
