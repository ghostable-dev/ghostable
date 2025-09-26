<?php

namespace App\Environment\Validation\Entities;

use Illuminate\Contracts\Validation\ValidationRule;
use Spatie\LaravelData\Data;

class EnvironmentValidationPlan extends Data
{
    /**
     * @param  FieldRules[]  $fieldRules
     * @param  ValidationRule[]  $envRules
     * */
    public function __construct(
        public array $fieldRules = [],
        public array $envRules = [],
    ) {}

    /** @return FieldRules[] */
    public function fields(): array
    {
        return $this->fieldRules;
    }

    /** @return ValidationRule[] */
    public function env(): array
    {
        return $this->envRules;
    }
}
