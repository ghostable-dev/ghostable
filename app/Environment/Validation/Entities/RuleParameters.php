<?php

namespace App\Environment\Validation\Entities;

use App\Environment\Validation\Models\EnvironmentVariableRule;
use Spatie\LaravelData\Data;

final class RuleParameters extends Data
{
    public function __construct(
        public ?int $min = null,
        public ?int $max = null,
        public array $allowedValues = [],
    ) {}
    
    /**
     * Create a RuleParameters entity from an EnvironmentVariableRule model.
     */
    public static function fromEnvironmentVariableRule(
        EnvironmentVariableRule $rule
    ): self
    {
        return new self(
            min: $rule->min,
            max: $rule->max,
            allowedValues: $rule->allowed_values ?? [],
        );
    }
}
