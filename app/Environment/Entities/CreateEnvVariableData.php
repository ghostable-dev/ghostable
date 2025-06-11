<?php

namespace App\Environment\Entities;

use App\Account\Models\User;
use App\Environment\Models\Environment;

class CreateEnvVariableData
{
    public function __construct(
        public Environment $environment,
        public string $key,
        public string $value,
        public bool $is_commented = false,
        public ?User $createdBy = null,
    ) {}
}
