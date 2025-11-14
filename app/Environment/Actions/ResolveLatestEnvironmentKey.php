<?php

declare(strict_types=1);

namespace App\Environment\Actions;

use App\Environment\Models\Environment;
use App\Environment\Models\EnvironmentKey;

class ResolveLatestEnvironmentKey
{
    public function handle(Environment $environment): ?EnvironmentKey
    {
        return $environment->keys()
            ->orderByDesc('version')
            ->first();
    }
}
