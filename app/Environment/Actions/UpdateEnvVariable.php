<?php

namespace App\Environment\Actions;

use App\Environment\Models\Environment;

class UpdateEnvVariable
{
    public static function handle(
        Environment $env,
        string $key,
        string $value,
        bool $is_commented = false
    ): void {
        $env->variables()->where('key', $key)->update([
            'value' => $value,
            'is_commented' => $is_commented,
        ]);
    }
}
