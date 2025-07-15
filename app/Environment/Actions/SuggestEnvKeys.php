<?php

namespace App\Environment\Actions;

use App\Environment\Models\Environment;
use App\Environment\Registry\EnvironmentVariableRegistry;

class SuggestEnvKeys
{
    /**
     * Generate grouped suggestions for environment variable keys
     * that are not yet used in the given environment.
     *
     * @return array<string, array<int, string>>
     */
    public function handle(Environment $environment): array
    {
        $registry = app(EnvironmentVariableRegistry::class);
        $existingKeys = $environment->variables->pluck('key')->map(fn ($k) => strtoupper($k));

        // 1. Get all known keys from the registry, grouped by their defined group
        $grouped = collect($registry->all())
            ->reject(fn ($def) => $existingKeys->contains($def->key()))
            ->groupBy(fn ($def) => $def->group()->name)
            ->map(fn ($defs) => $defs->map->key()->values()->all());

        // 2. Get keys from other environments in the project
        $projectKeys = $environment->project->environments()
            ->where('id', '!=', $environment->id)
            ->with('variables')
            ->get()
            ->flatMap(fn ($env) => $env->variables->pluck('key'))
            ->map(fn ($k) => strtoupper($k))
            ->unique()
            ->reject(fn ($key) => $existingKeys->contains($key))
            ->reject(fn ($key) => $registry->get($key)) // already known
            ->values();

        if ($projectKeys->isNotEmpty()) {
            $grouped->put('Other Project Keys', $projectKeys->all());
        }

        return $grouped->toArray();
    }
}
