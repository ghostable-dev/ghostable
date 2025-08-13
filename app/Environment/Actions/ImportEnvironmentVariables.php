<?php

namespace App\Environment\Actions;

use App\Account\Models\User;
use App\Environment\Entities\PushEnvironmentStrategy;
use App\Environment\Entities\PushResultData;
use App\Environment\Models\Environment;

class ImportEnvironmentVariables
{
    /**
     * Process and import environment variables from a raw environment file string.
     *
     * Splits the raw input into individual lines, pushes the variables into the given
     * environment (silently, without triggering notifications), and logs the import
     * activity including counts of added, updated, and removed variables.
     */
    public function handle(Environment $environment, string $rawInput, ?User $importedBy = null): void
    {
        $lines = $this->getLines($rawInput);
        if (! $lines) {
            return;
        }

        $results = app(PushEnvironment::class)->handle(
            env: $environment,
            incomingRaw: $lines,
            strategy: new PushEnvironmentStrategy(
                suppressOverrideOnRemoval: true,
                reinstateDeleted: false,
                silently: true
            ),
        );

        $this->log(
            environment: $environment,
            results: $results,
            importedBy: $importedBy
        );
    }

    /**
     * Split a string into an array of lines.
     *
     * This method normalizes all common line endings (`\n`, `\r\n`, `\r`)
     * and splits the input string accordingly.
     */
    protected function getLines(string $input): array|false
    {
        return preg_split('/\r\n|\n|\r/', $input);
    }

    /**
     * Record an import activity for an environment.
     *
     * Creates an activity log entry under the "variable" log name, noting which
     * environment was updated, the user who performed the import (if any), and
     * the counts of added, updated, and removed variables from the import result.
     */
    protected function log(
        Environment $environment,
        PushResultData $results,
        ?User $importedBy = null,
    ): void {
        activity('variable')
            ->performedOn($environment)
            ->causedBy($importedBy)
            ->event('imported')
            ->withProperties([
                'added' => $results->added,
                'updated' => $results->updated,
                'removed' => $results->removed,
            ])->log("Imported environment file to \"{$environment->name}\"");
    }
}
