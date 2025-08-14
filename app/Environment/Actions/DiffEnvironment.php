<?php

namespace App\Environment\Actions;

use App\Environment\Entities\DiffResultData;
use App\Environment\Entities\EnvLine;
use App\Environment\Models\Environment;
use App\Environment\Resolvers\ResolveEnvironmentVariables;
use App\Environment\Services\EnvParser;
use App\Environment\Variable\Actions\NormalizeVariableKey;
use Illuminate\Support\Collection;

class DiffEnvironment
{
    /**
     * Determine the differences between the provided variables and the environment's current state.
     *
     * @param  array<int, string>  $incomingRaw  Raw .env lines
     */
    public function handle(Environment $env, array $incomingRaw): DiffResultData
    {
        $parser = new EnvParser;
        $incoming = $this->normalizeIncoming($parser->parse($incomingRaw));

        $existing = $this->loadExisting($env)
            ->filter(fn ($dto) => ! $dto->variable->is_deleted);

        $added = [];
        $updated = [];
        $removed = [];

        foreach ($incoming as $key => $line) {
            if ($existing->has($key)) {
                $var = $existing[$key]->variable;

                if ($var->value !== $line->value || (bool) $var->is_commented !== (bool) $line->commented) {
                    $updated[$key] = [
                        'current' => [
                            'value' => $var->value,
                            'commented' => (bool) $var->is_commented,
                        ],
                        'incoming' => [
                            'value' => $line->value,
                            'commented' => (bool) $line->commented,
                        ],
                    ];
                }

                continue;
            }

            $added[$key] = [
                'value' => $line->value,
                'commented' => (bool) $line->commented,
            ];
        }

        foreach ($existing as $key => $dto) {
            if (! $incoming->has($key)) {
                $removed[$key] = [
                    'value' => $dto->variable->value,
                    'commented' => (bool) $dto->variable->is_commented,
                ];
            }
        }

        return new DiffResultData(
            added: $added,
            updated: $updated,
            removed: $removed,
        );
    }

    /**
     * Normalize EnvLine objects into a keyed collection by key.
     *
     * @param  array<int, EnvLine>  $raw
     * @return Collection<string, EnvLine>
     */
    private function normalizeIncoming(array $raw): Collection
    {
        return collect($raw)
            ->filter(fn (EnvLine $line) => $line->isValid())
            ->map(function (EnvLine $line) {
                $line->key = app(NormalizeVariableKey::class)->handle($line->key ?? '');

                return $line;
            })
            ->keyBy(fn ($line) => $line->key);
    }

    /**
     * Load the existing environment variables keyed by `key`.
     *
     * @return Collection<string, \App\Environment\Entities\ResolvedVariableData>
     */
    private function loadExisting(Environment $env): Collection
    {
        return resolve(ResolveEnvironmentVariables::class)
            ->handle($env)
            ->keyBy(fn ($dto) => $dto->variable->key);
    }
}
