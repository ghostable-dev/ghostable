<?php

namespace App\Environment\Actions;

class NormalizeEnvironmentName
{
    /**
     * Normalize an environment name string into lowercase kebab case.
     *
     * Example: "local Joe" → "local-joe"
     */
    public function handle(string $raw): string
    {
        // Allow only letters, numbers, and dashes
        $normalized = preg_replace('/[^A-Z0-9_]/i', '-', $raw);

        // Collapse consecutive dashes
        $normalized = preg_replace('/_+/', '-', $normalized);

        return strtolower($normalized);
    }
}
