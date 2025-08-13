<?php

namespace App\Environment\Actions;

use App\Environment\Entities\EnvLine;
use App\Environment\Entities\PushEnvironmentStrategy;
use App\Environment\Entities\PushResultData;
use App\Environment\Models\Environment;
use App\Environment\Resolvers\ResolveEnvironmentVariables;
use App\Environment\Services\EnvParser;
use App\Environment\Variable\Actions\CreateVariable;
use App\Environment\Variable\Actions\DeleteVariable;
use App\Environment\Variable\Actions\NormalizeVariableKey;
use App\Environment\Variable\Actions\ReinstateInheritedVariable;
use App\Environment\Variable\Actions\ReinstateOverrideVariable;
use App\Environment\Variable\Actions\SuppressInheritedVariable;
use App\Environment\Variable\Actions\SuppressOverrideVariable;
use App\Environment\Variable\Actions\UpdateVariable;
use App\Environment\Variable\Entities\CreateVariableData;
use App\Environment\Variable\Entities\UpdateVariableData;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class PushEnvironment
{
    /**
     * Push a new set of variables to the given environment.
     *
     * @param  array<int, string>  $incomingRaw  Raw .env lines
     */
    public function handle(
        Environment $env,
        array $incomingRaw,
        ?PushEnvironmentStrategy $strategy = null
    ): PushResultData {
        $strategy ??= new PushEnvironmentStrategy;

        $parser = new EnvParser;
        $incoming = $this->normalizeIncoming($parser->parse($incomingRaw));

        $existing = $this->loadExisting($env); // keyed by key
        $activeExisting = $existing->filter(fn ($dto) => ! $dto->variable->is_deleted);
        $tombstones = $existing->filter(fn ($dto) => $dto->variable->is_deleted && $dto->variable->environment_id === $env->id);

        $added = collect();      // Collection<string, EnvLine>
        $updated = collect();    // Collection<string, EnvLine>
        $removed = collect();    // Collection<int, string>

        foreach ($incoming as $key => $line) {
            if ($activeExisting->has($key)) {
                $var = $activeExisting[$key]->variable;

                if ($var->value !== $line->value || (bool) $var->is_commented !== (bool) $line->commented) {
                    if ($var->environment_id === $env->id) {
                        $updated->put($key, $line);
                    } else {
                        $added->put($key, $line); // override inherited
                    }
                }

                continue;
            }

            if ($tombstones->has($key) && $strategy->reinstateDeleted) {
                $tomb = $tombstones[$key]->variable;
                if ($tomb->is_override) {
                    app(ReinstateOverrideVariable::class)->handle($tomb, Auth::user());
                    $updated->put($key, $line);
                } else {
                    app(ReinstateInheritedVariable::class)->handle($tomb, Auth::user());
                    $added->put($key, $line);
                }

                continue;
            }

            $added->put($key, $line);
        }

        foreach ($activeExisting as $key => $dto) {
            if (! $incoming->has($key)) {
                $removed->push($key);
            }
        }

        $ancestor = $env->base
            ? resolve(ResolveEnvironmentVariables::class)->handle($env->base)->keyBy(fn ($dto) => $dto->variable->key)
            : collect();

        $this->applyChanges($env, $added, $updated, $removed, $strategy, $ancestor);

        if (! $strategy->silently) {
            activity('variable')
                ->performedOn($env)
                ->causedBy(Auth::user())
                ->event('push')
                ->withProperties([
                    'added' => $added->count(),
                    'updated' => $updated->count(),
                    'removed' => $removed->count(),
                ])->log("Pushed environment file to \"{$env->name}\"");
        }

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
        Collection $added,
        Collection $updated,
        Collection $removed,
        PushEnvironmentStrategy $strategy,
        Collection $ancestor
    ): void {

        foreach ($added as $key => $line) {
            $isOverride = $ancestor->has($key);

            app(CreateVariable::class)->handle(
                new CreateVariableData(
                    environment: $env,
                    key: $line->key ?? '',
                    value: $line->value ?? '',
                    is_commented: $line->commented ?? false,
                    is_override: $isOverride,
                    createdBy: Auth::user(),
                )
            );
        }

        foreach ($updated as $key => $line) {
            $varToUpdate = $env->findVariableForKey($key);
            if (! $varToUpdate) {
                continue;
            }

            app(UpdateVariable::class)->handle(
                new UpdateVariableData(
                    variable: $varToUpdate,
                    value: $line->value ?? '',
                    is_commented: $line->commented ?? false,
                    updatedBy: Auth::user(),
                )
            );
        }

        foreach ($removed as $key) {
            $var = $env->findVariableForKey($key);

            if (! $var) {
                if ($strategy->suppressInheritedOnRemoval) {
                    app(SuppressInheritedVariable::class)->handle(
                        key: $key,
                        environment: $env,
                        suppressedBy: Auth::user()
                    );
                }

                continue;
            }

            if ($var->is_override && $strategy->suppressOverrideOnRemoval) {
                app(SuppressOverrideVariable::class)->handle(
                    var: $var,
                    suppressedBy: Auth::user()
                );

                continue;
            }

            app(DeleteVariable::class)->handle(
                var: $var,
                deletedBy: Auth::user()
            );
        }
    }
}
