<?php

namespace App\Environment\Actions;

use App\Environment\Enums\EnvFileFormat;
use Illuminate\Support\Collection;

class RenderEnvironmentVariables
{
    public function handle(
        Collection $variables,
        EnvFileFormat $format = EnvFileFormat::ALPHABETICAL
    ): string {
        return match ($format) {
            EnvFileFormat::ALPHABETICAL => $this->renderAlphabetically($variables),
            EnvFileFormat::GROUPED => $this->renderGrouped($variables, false),
            EnvFileFormat::GROUPED_COMMENTS => $this->renderGrouped($variables, true),
        };
    }

    protected function renderAlphabetically(Collection $variables): string
    {
        return $variables
            ->sortBy('key')
            ->map(fn ($var) => $this->formatLine($var->key, $var->value, $var->is_commented))
            ->implode(PHP_EOL);
    }

    protected function renderGrouped(Collection $variables, bool $withComments = false): string
    {
        $groups = $variables
            ->groupBy(fn ($var) => strtoupper(strtok($var->key, '_')))
            ->sortKeys();

        $lines = collect();

        foreach ($groups as $prefix => $vars) {
            if ($withComments) {
                $lines->push("# {$prefix}");
            }

            $vars->sortBy('key')->each(function ($var) use ($lines) {
                $lines->push($this->formatLine($var->key, $var->value, $var->is_commented));
            });

            $lines->push('');
        }

        return rtrim($lines->implode(PHP_EOL));
    }

    protected function formatLine(string $key, string $value, bool $commented): string
    {
        $line = "{$key}={$this->escapeValue($value)}";

        return $commented ? "# {$line}" : $line;
    }

    protected function escapeValue(string $value): string
    {
        if (preg_match('/\s|["\'$`\\\\]/', $value)) {
            return '"'.addcslashes($value, '"\\').'"';
        }

        return $value;
    }
}
