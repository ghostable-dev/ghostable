<?php

namespace App\Environment\Actions;

use App\Environment\Models\Environment;
use App\Environment\Services\EnvParser;
use App\Environment\Entities\EnvLine
use Illuminate\Support\Collection;

class PushEnvironmentVariables
{
    /**
     * Push a new set of variables to the given environment.
     *
     * This compares the incoming variables with existing ones and only performs
     * database operations where necessary — adding, updating, or deleting keys.
     *
     * @param Environment $env
     * @param array<int, array{key: string, value: string|null}> $incomingRaw
     * @return array{status: string, added: int, updated: int, removed: int}
     */
    public static function handle(Environment $env, array $incomingRaw): array
    {
        $parser = new EnvParser();
        $incoming = self::normalizeIncoming($parser->parse($incomingRaw));
        $existing = self::loadExisting($env);

        $added = $incoming->keys()->diff($existing->keys());
        $removed = $existing->keys()->diff($incoming->keys());
        $updated = self::findUpdates($incoming, $existing);

        self::applyChanges($env, $incoming, $added, $updated, $removed);

        return [
            'status' => 'updated',
            'added' => $added->count(),
            'updated' => $updated->count(),
            'removed' => $removed->count(),
        ];
    }

    /**
     * Normalize the incoming raw variables into a keyed collection.
     *
     * @param array<int, EnvLine> $raw
     * @return \Illuminate\Support\Collection<string, string>
     */
    private static function normalizeIncoming(array $raw): Collection
    {
        return collect($raw)
            ->filter(fn($line) => !is_null($line->key))
            ->mapWithKeys(fn ($line) => [
                $line->key => $line->value ?? '',
            ]);
    }

    /**
     * Load the current variables from the environment as a keyed collection.
     *
     * @param Environment $env
     * @return \Illuminate\Support\Collection<string, array{id: int, value: string}>
     */
    private static function loadExisting(Environment $env): Collection
    {
        return $env->variables()
            ->get(['id', 'key', 'value'])
            ->mapWithKeys(fn ($var) => [
                $var->key => ['id' => $var->id, 'value' => $var->value],
            ]);
    }

    /**
     * Determine which existing keys have changed values.
     *
     * @param Collection<string, string> $incoming
     * @param Collection<string, array{id: int, value: string}> $existing
     * @return Collection<string, string>
     */
    private static function findUpdates(Collection $incoming, Collection $existing): Collection
    {
        return $incoming->filter(fn ($value, $key) =>
            $existing->has($key) && $existing[$key]['value'] !== $value
        );
    }

    /**
     * Apply the changes to the environment.
     *
     * @param Environment $env
     * @param Collection<string, string> $incoming
     * @param Collection<int, string> $added
     * @param Collection<string, string> $updated
     * @param Collection<int, string> $removed
     * @return void
     */
    private static function applyChanges(
        Environment $env,
        Collection $incoming,
        Collection $added,
        Collection $updated,
        Collection $removed
    ): void {
        foreach ($added as $key) {
            $env->variables()->create([
                'key' => $key,
                'value' => $incoming[$key],
            ]);
        }

        foreach ($updated as $key => $value) {
            $env->variables()->where('key', $key)->update([
                'value' => $value,
            ]);
        }

        foreach ($removed as $key) {
            $env->variables()->where('key', $key)->delete();
        }
    }
}