<?php

declare(strict_types=1);

namespace App\Integration\Support;

class IntegrationManager
{
    /**
     * @var array<string, array{name: string, description: string, oauth?: bool, color?: string, logo?: string, landing_page_url?: string, scopes?: array<int, string>}>
     */
    protected array $integrations = [];

    /**
     * Register an integration and its display metadata.
     *
     * @param  array{name: string, description: string, oauth?: bool, color?: string, logo?: string, landing_page_url?: string, scopes?: array<int, string>}  $meta
     */
    public function register(string $key, array $meta): void
    {
        $this->integrations[$key] = $meta;
    }

    /**
     * @return array<string, array{name: string, description: string, oauth?: bool, color?: string, logo?: string, landing_page_url?: string, scopes?: array<int, string>}>
     */
    public function available(): array
    {
        return $this->integrations;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->integrations);
    }

    /**
     * @return array{name: string, description: string, oauth?: bool, color?: string, logo?: string, landing_page_url?: string, scopes?: array<int, string>}|null
     */
    public function get(string $key): ?array
    {
        return $this->integrations[$key] ?? null;
    }
}
