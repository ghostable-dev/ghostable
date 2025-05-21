<?php

namespace App\Environment\Actions;

use App\Environment\Models\Environment;

class RenderEnvFile
{
    public static function handle(Environment $env): string
    {
        return $env->variables()
            ->orderBy('key') // simple default ordering for now
            ->get(['key', 'value', 'is_commented'])
            ->map(function ($var) {
                $line = "{$var->key}={$var->value}";
                return $var->is_commented ? "#{$line}" : $line;
            })->implode(PHP_EOL);
    }
}