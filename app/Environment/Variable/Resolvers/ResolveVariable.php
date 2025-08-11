<?php

namespace App\Environment\Variable\Resolvers;

use App\Environment\Variable\Models\EnvironmentVariable;

class ResolveVariable
{
    public static function onceWithContext(string $variableId): EnvironmentVariable
    {
        return once(function () use ($variableId) {
            return EnvironmentVariable::with([
                'environment',
                'versions',
                'latestVersion',
                'lastUpdatedBy',
            ])->findOrFail($variableId);
        }, "variable:withContext:{$variableId}");
    }
}
