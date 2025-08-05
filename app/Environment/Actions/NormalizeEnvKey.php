<?php

namespace App\Environment\Actions;

class NormalizeEnvKey
{
    /**
     * Normalize an environment variable key string into uppercase snake case.
     *
     * Example: "app url" → "APP_URL"
     */
    public function handle(string $raw): string
    {
        // Allow only letters, numbers, and underscores
        $normalized = preg_replace('/[^A-Z0-9_]/i', '_', $raw);

        // Collapse consecutive underscores
        $normalized = preg_replace('/_+/', '_', $normalized);

        return strtoupper($normalized);
    }
}
