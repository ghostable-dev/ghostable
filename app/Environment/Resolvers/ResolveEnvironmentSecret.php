<?php

namespace App\Environment\Resolvers;

use App\Environment\Models\EnvironmentSecret;

class ResolveEnvironmentSecret
{
    public static function onceWithContext(string $secretId): EnvironmentSecret
    {
        return once(function () use ($secretId) {
            return EnvironmentSecret::withTrashed()->with([
                'environment.project.organization',
                'note.createdBy',
                'note.lastUpdatedBy',
                'comments.createdBy',
                'versions.changedBy',
                'versions.changeNote.createdBy',
                'latestVersion.changeNote.createdBy',
                'lastUpdatedBy',
            ])->findOrFail($secretId);
        }, "secret:withContext:{$secretId}");
    }
}
