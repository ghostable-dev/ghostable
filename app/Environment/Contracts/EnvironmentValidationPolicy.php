<?php

namespace App\Environment\Contracts;

use App\Environment\Models\Environment;

interface EnvironmentValidationPolicy
{
    /**
     * Validate an environment using context-specific rules.
     *
     * @return array<string, string> key => error message
     */
    public function validate(Environment $environment): array;
}