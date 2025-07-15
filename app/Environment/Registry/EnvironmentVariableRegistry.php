<?php

namespace App\Environment\Registry;

class EnvironmentVariableRegistry
{
    /** @var array<string, EnvironmentVariableDefinition> */
    protected array $definitions = [];

    public function register(EnvironmentVariableDefinition $definition): void
    {
        $this->definitions[strtoupper($definition->key())] = $definition;
    }

    public function get(string $key): ?EnvironmentVariableDefinition
    {
        return $this->definitions[strtoupper($key)] ?? null;
    }

    public function all(): array
    {
        return $this->definitions;
    }
}
