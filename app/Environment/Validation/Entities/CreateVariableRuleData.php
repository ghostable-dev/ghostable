<?php

namespace App\Environment\Validation\Entities;

use App\Account\Models\User;
use App\Environment\Models\Environment;
use App\Environment\Validation\Enums\EnvironmentVariableRuleType;
use Spatie\LaravelData\Data;

class CreateVariableRuleData extends Data
{
    public function __construct(
        public Environment $environment,
        public string $key,
        public bool $isRequired,
        public EnvironmentVariableRuleType|string $type,
        public ?int $min,
        public ?int $max,
        public array $allowedValues = [],
        public ?string $description = null,
        public bool $isOverride = false,
        public bool $isDeleted = false,
        public ?User $createdBy = null,
    ) {
        if (is_string($this->type)) {
            $this->type = EnvironmentVariableRuleType::from($this->type);
        }
    }
}
