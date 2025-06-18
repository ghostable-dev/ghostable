<?php

namespace App\Project\Resolvers;

use App\Project\Models\Project;

class ResolveProject
{
    public static function onceWithContext(string $id): Project
    {
        return once(function () use ($id) {
            return Project::with(['environments'])->findOrFail($id);
        }, "project:withContext:{$id}");
    }
}