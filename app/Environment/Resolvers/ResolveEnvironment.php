<?php

namespace App\Environment\Resolvers;

use App\Environment\Models\Environment;

class ResolveEnvironment
{
    public static function onceWithContext(string $envId): Environment
    {
        return once(function () use ($envId) {
            return Environment::with([
                'variables',
                'project.environments',
                'project.team',
                'project.team.projects',
            ])->findOrFail($envId);
        }, "environment:withContext:{$envId}");
    }
}
