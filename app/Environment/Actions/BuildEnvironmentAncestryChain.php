<?php

namespace App\Environment\Actions;

use App\Environment\Models\Environment;

class BuildEnvironmentAncestryChain
{
    /**
     * @return array<Environment>
     */
    public static function handle(Environment $env): array
    {
        $chain = [];

        // Walk up the tree until there is no base
        while ($env) {
            $chain[] = $env;
            $env = $env->base; // Assumes `base()` is a `belongsTo`
        }

        // Reverse so it reads from root → current
        return array_reverse($chain);
    }
}