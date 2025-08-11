<?php

namespace App\Environment\Variable\Actions;

use App\Environment\Variable\Registry\VariableRegistry;

class GetSuggestedVariableValues
{
    /**
     * Get suggested values for a given env variable key.
     *
     * @return array<int, string>
     */
    public function handle(string $key): array
    {
        $definition = app(VariableRegistry::class)->get($key);

        return $definition?->suggestedValues() ?? [];
    }
}
