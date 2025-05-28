<?php

namespace App\Environment\Actions;

use App\Environment\Models\Environment;

class CreateEnvVariable
{
    public static function handle(
        Environment $env,
        string $key,
        string $value,
        bool $is_commented = false
    ): void {
        $env->variables()->create([
            'key' => $key,
            'value' => $value,
            'is_commented' => $is_commented,
        ]);
    }
}
