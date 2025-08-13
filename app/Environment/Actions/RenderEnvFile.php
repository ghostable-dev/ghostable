<?php

namespace App\Environment\Actions;

use App\Environment\Enums\EnvFileFormat;
use App\Environment\Models\Environment;

class RenderEnvFile
{
    public static function handle(Environment $env, ?EnvFileFormat $format = null): string
    {
        $variables = resolve(ResolveEnvironmentVariables::class)
            ->handle($env)
            ->map(fn ($var) => (object) $var->only(['key', 'value', 'is_commented']))
            ->values();

        $format ??= $env->file_format ?? EnvFileFormat::ALPHABETICAL;

        if ($format === EnvFileFormat::ALPHABETICAL) {
            return $variables->sortBy('key')
                ->map(fn ($var) => self::formatLine($var->key, $var->value, $var->is_commented))
                ->implode(PHP_EOL);
        }

        $groups = $variables->groupBy(function ($var) {
            return strtoupper(strtok($var->key, '_'));
        })->sortKeys();

        $lines = collect();

        foreach ($groups as $prefix => $vars) {
            if ($format === EnvFileFormat::GROUPED_COMMENTS) {
                $lines->push("# {$prefix}");
            }

            $vars->sortBy('key')->each(function ($var) use ($lines) {
                $lines->push(self::formatLine($var->key, $var->value, $var->is_commented));
            });

            $lines->push('');
        }

        return rtrim($lines->implode(PHP_EOL));
    }

    protected static function formatLine(string $key, string $value, bool $commented): string
    {
        $value = self::escapeValue($value);
        $line = "{$key}={$value}";

        return $commented ? "#{$line}" : $line;
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
