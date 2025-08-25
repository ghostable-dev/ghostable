<?php

namespace App\Organization\Resolvers;

use App\Organization\Models\Organization;

class ResolveOrganization
{
    public static function onceWithContext(string $id): Organization
    {
        return once(function () use ($id) {
            return Organization::with([])->findOrFail($id);
        }, "organization:withContext:{$id}");
    }
}
