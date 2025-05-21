<?php

namespace App\Environment\Actions;

use App\Environment\Models\Environment;

class DeleteEnvVariable
{
    public static function handle(
        Environment $env,
        string $key
    ): void
    {
        $env->variables()->where('key', $key)->delete();
    }
}