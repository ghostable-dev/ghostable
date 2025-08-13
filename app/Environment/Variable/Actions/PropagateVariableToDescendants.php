<?php

namespace App\Environment\Variable\Actions;

use App\Environment\Models\Environment;
use App\Environment\Variable\Models\EnvironmentVariable;

class PropagateVariableToDescendants
{
    /**
     * When a new variable is added to an environment, convert any matching
     * variables in descendant environments into overrides (or remove them if
     * they are identical) so inheritance remains consistent.
     */
    public function handle(EnvironmentVariable $variable): void
    {
        $environment = $variable->environment;
        $environment->load('derived');

        foreach ($environment->derived as $child) {
            $this->applyToEnvironment($child, $variable->key, $variable->value);
        }
    }

    protected function applyToEnvironment(Environment $environment, string $key, string $ancestorValue): void
    {
        $local = $environment->variables()->where('key', $key)->first();

        if ($local) {
            if (! $local->is_deleted && ! $local->is_override) {
                if ($local->value === $ancestorValue) {
                    $local->delete();
                } else {
                    $local->is_override = true;
                    $local->save();
                }
            }
        }

        $environment->load('derived');

        foreach ($environment->derived as $child) {
            $this->applyToEnvironment($child, $key, $ancestorValue);
        }
    }
}
