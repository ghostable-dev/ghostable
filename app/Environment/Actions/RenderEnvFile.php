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
                $value = self::escapeValue($var->value);
                $line = "{$var->key}={$value}";

                return $var->is_commented ? "#{$line}" : $line;
            })->implode(PHP_EOL);
    }

    protected static function escapeValue(string $value): string
    {
        // If the value contains special characters, wrap it in double quotes
        if (preg_match('/\s|["\'$`\\\\]/', $value)) {
            // Escape inner quotes and backslashes
            $escaped = addcslashes($value, '"\\');

            return "\"{$escaped}\"";
        }

        return $value;
    }
}
