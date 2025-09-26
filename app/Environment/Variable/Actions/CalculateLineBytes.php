<?php

namespace App\Environment\Variable\Actions;

use App\Environment\Variable\Models\EnvironmentVariable;

class CalculateLineBytes
{
    /**
     * Calculate the total byte size for a KEY=VALUE\n line.
     */
    public function handle(EnvironmentVariable $variable): int
    {
        $line = "{$variable->key}={$variable->value}\n";

        return strlen($line);
    }
}
