<?php

namespace App\Environment\Variable\Entities;

use App\Account\Models\User;
use App\Environment\Models\Environment;

class CreateVariableData
{
    public bool $is_override = false;

    public function __construct(
        public Environment $environment,
        public string $key,
        public string $value,
        public bool $is_commented = false,
        public bool $vapor_secret = false,
        ?bool $is_override = null,
        public bool $is_deleted = false,
        public ?User $createdBy = null,
    ) {
        $this->is_override = $is_override ?? $this->determineIsOverride();
    }

    protected function determineIsOverride(): bool
    {
        $base = $this->environment->base;

        while ($base) {
            if ($base->variables()
                ->where('key', $this->key)
                ->exists()
            ) {
                return true;
            }

            $base = $base->base;
        }

        return false;
    }
}
