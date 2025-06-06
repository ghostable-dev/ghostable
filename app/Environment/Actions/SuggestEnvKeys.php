<?php

namespace App\Environment\Actions;

use App\Environment\Enums\CommonEnvKey;
use App\Environment\Models\Environment;

class SuggestEnvKeys
{
    /**
     * Generate grouped suggestions for environment variable keys.
     *
     * @return array<string, array<int, string>>
     */
    public function handle(Environment $environment): array
    {
        // Get keys from other environments in the same project
        $otherKeys = $environment->project->environments()
            ->where('id', '!=', $environment->id)
            ->with('variables')
            ->get()
            ->flatMap(fn ($env) => $env->variables->pluck('key'))
            ->unique()
            ->values();

        $standardGroups = CommonEnvKey::grouped();
        $existingKeys = $environment->variables->pluck('key');

        // Track which standard keys we've already used
        $standardKeys = collect($standardGroups)->flatten();

        // Filter out used keys from each group
        $filteredGroups = collect($standardGroups)->map(function ($keys) use ($existingKeys) {
            return collect($keys)
                ->reject(fn ($key) => $existingKeys->contains($key))
                ->values()
                ->all();
        })->filter(fn ($group) => ! empty($group));

        // Find project keys not in the standard set
        $customProjectKeys = $otherKeys
            ->reject(fn ($key) => $standardKeys->contains($key) || $existingKeys->contains($key))
            ->values()
            ->all();

        if (! empty($customProjectKeys)) {
            $filteredGroups->put('Other Project Keys', $customProjectKeys);
        }

        return $filteredGroups->toArray();
    }
}
