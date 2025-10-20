<?php

namespace App\Environment\Resolvers;

use App\Environment\Models\EnvironmentSecret;

class ResolveEnvironmentSecret
{
    public static function onceWithContext(string $secretId): EnvironmentSecret
    {
        return once(function () use ($secretId) {
            return EnvironmentSecret::with([
                'environment',
                'versions',
                'latestVersion',
                'lastUpdatedBy',
            ])->findOrFail($secretId);
        }, "secret:withContext:{$secretId}");
    }
}
