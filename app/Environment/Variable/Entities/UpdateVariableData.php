<?php

namespace App\Environment\Variable\Entities;

use App\Account\Models\User;
use App\Environment\Variable\Models\EnvironmentVariable;

class UpdateVariableData
{
    public function __construct(
        public EnvironmentVariable $variable,
        public string $value,
        public ?bool $vapor_secret = null,
        public ?bool $is_commented = null,
        public ?bool $is_override = null,
        public ?User $updatedBy = null,
    ) {}
}
