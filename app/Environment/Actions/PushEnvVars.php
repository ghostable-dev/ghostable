<?php

namespace App\Environment\Actions;

use App\Environment\Entities\CreateEnvVariableData;
use App\Environment\Entities\UpdateEnvVariableData;
use App\Environment\Entities\EnvLine;
use App\Environment\Entities\PushResultData;
use App\Environment\Models\Environment;
use App\Environment\Models\EnvironmentVariable;
use App\Environment\Services\EnvParser;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class PushEnvVars
{
    /**
     * Push a new set of variables to the given environment.
     *
     * @param  array<int, string>  $incomingRaw  Raw .env lines
     */
    public function handle(
        Environment $env,
        array $incomingRaw
    ): PushResultData {
        $parser = new EnvParser;
        $incoming = $this->normalizeIncoming($parser->parse($incomingRaw));
        $existing = $this->loadExisting($env);

        $added = $incoming->keys()->diff($existing->keys());
        $removed = $existing->keys()->diff($incoming->keys());
        $updated = $this->findUpdates($incoming, $existing);

        $this->applyChanges($env, $incoming, $added, $updated, $removed);
        
        // Log results
        activity('variable')
            ->performedOn($env)
            ->causedBy(Auth::user())
            ->event('push')
            ->withProperties([
                'added' => $added->count(),
                'updated' => $updated->count(),
                'removed' => $removed->count(),
            ])->log("Pushed environment file to \"{$env->name}\"");

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
    private function normalizeIncoming(array $raw): Collection
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
    private function loadExisting(Environment $env): Collection
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
    private function findUpdates(Collection $incoming, Collection $existing): Collection
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
    private function applyChanges(
        Environment $env,
        Collection $incoming,
        Collection $added,
        Collection $updated,
        Collection $removed
    ): void {

        foreach ($added as $key) {
            app(CreateEnvVariable::class)->handle(
                $this->toCreateVariableData($env, $incoming[$key])
            );
        }

        foreach ($updated as $key => $line) {
            $varToUpdate = $env->findVariableForKey($key);
            app(UpdateEnvVariable::class)->handle(
                $this->toUpdateVariableData($varToUpdate, $line)
            );
        }

        foreach ($removed as $key) {
            $varToDelete = $env->findVariableForKey($key);
            if ($varToDelete) {
                app(DeleteEnvVariable::class)->handle(
                    var: $varToDelete,
                    deletedBy: Auth::user()
                );
            }
        }
    }

    /**
     * Transform an incoming item into a structured CreateEnvVariableData DTO.
     *
     * This is used to convert raw incoming data (e.g., from a push or batch operation)
     * into a standardized format for creating a new environment variable.
     */
    private function toCreateVariableData(
        Environment $env,
        object $item
    ): CreateEnvVariableData {
        return new CreateEnvVariableData(
            environment: $env,
            key: $item->key,
            value: $item->value ?? '',
            is_commented: $item->commented ?? false,
            createdBy: Auth::user(),
        );
    }

    /**
     * Transform an incoming data line and existing variable into an UpdateEnvVariableData DTO.
     *
     * This helper is used to prepare structured data for updating an environment variable,
     * typically as part of a batch or sync operation. It extracts the value and comment status
     * from the input line and associates the update with the authenticated user.
     */
    private function toUpdateVariableData(
        EnvironmentVariable $variable,
        object $line
    ): UpdateEnvVariableData {
        return new UpdateEnvVariableData(
            variable: $variable,
            value: $line->value ?? '',
            is_commented: $line->commented ?? false,
            updatedBy: Auth::user(),
        );
    }
}
