<?php

namespace App\Environment\Entities;

use App\Account\Models\User;
use App\Environment\Models\EnvironmentVariable;

class UpdateEnvVariableData
{
    public function __construct(
        public EnvironmentVariable $variable,
        public string $value,
        public ?bool $is_commented = null,
        public ?bool $is_override = null,
        public ?User $updatedBy = null,
    ) {}
}
