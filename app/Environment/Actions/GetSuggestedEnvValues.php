<?php

namespace App\Environment\Actions;

use App\Environment\Registry\EnvironmentVariableRegistry;

class GetSuggestedEnvValues
{
    /**
     * Get suggested values for a given env variable key.
     *
     * @param string $key
     * @return array<int, string>
     */
    public function handle(string $key): array
    {
        $definition = app(EnvironmentVariableRegistry::class)->get($key);
        
        return $definition?->suggestedValues() ?? [];
    }
}