<?php

namespace App\Environment\Variable\Registry;

class VariableRegistry
{
    /** @var array<string, VariableDefinition> */
    protected array $definitions = [];

    public function register(VariableDefinition $definition): void
    {
        $this->definitions[strtoupper($definition->key())] = $definition;
    }

    public function get(string $key): ?VariableDefinition
    {
        return $this->definitions[strtoupper($key)] ?? null;
    }

    public function all(): array
    {
        return $this->definitions;
    }
}
