<?php

namespace App\Environment\Actions;

use App\Environment\Entities\EnvLine;
use App\Environment\Entities\PushResultData;
use App\Environment\Models\Environment;
use App\Environment\Services\EnvParser;
use Illuminate\Support\Collection;

class PushEnvVars
{
    /**
     * Push a new set of variables to the given environment.
     *
     * @param  array<int, string>  $incomingRaw  Raw .env lines
     */
    public static function handle(
        Environment $env,
        array $incomingRaw
    ): PushResultData {
        $parser = new EnvParser;
        $incoming = self::normalizeIncoming($parser->parse($incomingRaw));
        $existing = self::loadExisting($env);

        $added = $incoming->keys()->diff($existing->keys());
        $removed = $existing->keys()->diff($incoming->keys());
        $updated = self::findUpdates($incoming, $existing);

        self::applyChanges($env, $incoming, $added, $updated, $removed);

        return new PushResultData(
            added: $added->count(),
            updated: $updated->count(),
            removed: $removed->count(),
        );
    }

    /**
     * Normalize EnvLine objects into a keyed collection by key.
     *
     * @param  array<int, EnvLine>  $raw
     * @return Collection<string, EnvLine>
     */
    private static function normalizeIncoming(array $raw): Collection
    {
        return collect($raw)
            ->filter(fn (EnvLine $line) => $line->isValid())
            ->keyBy(fn ($line) => $line->key);
    }

    /**
     * Load the existing environment variables keyed by `key`.
     *
     * @return Collection<string, array{id: int, value: string, is_commented: bool}>
     */
    private static function loadExisting(Environment $env): Collection
    {
        return $env->variables()
            ->get(['id', 'key', 'value', 'is_commented'])
            ->mapWithKeys(fn ($var) => [
                $var->key => [
                    'id' => $var->id,
                    'value' => $var->value,
                    'is_commented' => (bool) $var->is_commented,
                ],
            ]);
    }

    /**
     * Determine which existing variables need to be updated.
     *
     * @param  Collection<string, EnvLine>  $incoming
     * @param  Collection<string, array{id: int, value: string, is_commented: bool}>  $existing
     * @return Collection<string, EnvLine>
     */
    private static function findUpdates(Collection $incoming, Collection $existing): Collection
    {
        return $incoming->filter(function (EnvLine $line, string $key) use ($existing) {
            if (! $existing->has($key)) {
                return false;
            }

            $current = $existing[$key];

            return $line->value !== $current['value']
                || $line->commented !== $current['is_commented'];
        });
    }

    /**
     * Apply variable adds, updates, and deletes to the environment.
     *
     * @param  Collection<string, EnvLine>  $incoming
     * @param  Collection<int, string>  $added
     * @param  Collection<string, EnvLine>  $updated
     * @param  Collection<int, string>  $removed
     */
    private static function applyChanges(
        Environment $env,
        Collection $incoming,
        Collection $added,
        Collection $updated,
        Collection $removed
    ): void {

        foreach ($added as $key) {
            CreateEnvVariable::handle(
                env: $env,
                key: $incoming[$key]->key,
                value: $incoming[$key]->value ?? '',
                is_commented: $incoming[$key]->commented ?? false
            );
        }

        foreach ($updated as $key => $line) {
            UpdateEnvVariable::handle(
                env: $env,
                key: $line->key,
                value: $line->value ?? '',
                is_commented: $line->commented ?? false
            );
        }

        foreach ($removed as $key) {
            DeleteEnvVariable::handle(
                env: $env,
                key: $key
            );
        }
    }
}
