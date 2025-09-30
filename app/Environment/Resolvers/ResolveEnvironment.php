<?php

namespace App\Environment\Resolvers;

use App\Environment\Models\Environment;

class ResolveEnvironment
{
    public static function onceWithContext(string $envId): Environment
    {
        return once(function () use ($envId) {
            return Environment::with([
                'base',
                'derived',
                'variables',
                'project',
                'project.environments',
                'project.organization',
                'project.organization.projects',
            ])->findOrFail($envId);
        }, "environment:withContext:{$envId}");
    }
}
