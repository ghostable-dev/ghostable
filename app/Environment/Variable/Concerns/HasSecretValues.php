<?php

namespace App\Environment\Variable\Concerns;

trait HasSecretValues
{
    /**
     * Get the value for display, masking it if it's considered a secret.
     *
     * If the key is recognized as secret (per isSecret()), returns a fixed-length
     * masked string of bullet characters. Otherwise returns the raw value.
     */
    public function displayValue(): string
    {
        return $this->isSecret() ? str_repeat('•', 10) : $this->value ?? '';
    }

    /**
     * Determine whether this variable key should be treated as a secret.
     *
     * Checks the key name against a list of common secret-indicating substrings
     * (e.g. "key", "secret", "password", "token", "private", "credentials").
     * The check is case-insensitive and returns true if any of those patterns
     * appear in the key.
     */
    public function isSecret(): bool
    {
        if (property_exists($this, 'is_vapor_secret') && (bool) $this->is_vapor_secret) {
            return true;
        }

        return collect([
            'key',
            'secret',
            'password',
            'token',
            'private',
            'credentials',
        ])->contains(fn ($pattern) => str_contains(strtolower($this->key), $pattern));
    }
}
