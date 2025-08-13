<?php

namespace App\Environment\Entities;

use App\Environment\Variable\Models\EnvironmentVariable;
use Spatie\LaravelData\Data;

class ResolvedVariableData extends Data
{
    public function __construct(
        public EnvironmentVariable $variable,
        public bool $inherited,
        public bool $overridden,
        public bool $overrides,
        public string $origin,
    ) {}
}
