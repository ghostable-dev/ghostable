<?php

namespace App\Environment\Validation\Entities;

use App\Account\Models\User;
use App\Environment\Validation\Enums\EnvironmentVariableRuleType;
use App\Environment\Validation\Models\EnvironmentVariableRule;
use Spatie\LaravelData\Data;

class UpdateVariableRuleData extends Data
{
    public function __construct(
        public EnvironmentVariableRule $rule,
        public string $key,
        public bool $isRequired,
        public EnvironmentVariableRuleType|string $type,
        public ?int $min,
        public ?int $max,
        public array $allowedValues,
        public ?string $description,
        public User $updatedBy,
    ) {
        if (is_string($this->type)) {
            $this->type = EnvironmentVariableRuleType::from($this->type);
        }
    }
}