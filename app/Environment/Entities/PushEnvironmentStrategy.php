<?php

namespace App\Environment\Entities;

use App\Environment\Enums\PushMode;
use Spatie\LaravelData\Data;

class PushEnvironmentStrategy extends Data
{
    public function __construct(
        public PushMode $mode = PushMode::ADDITIVE,
        public bool $suppressInheritedOnRemoval = true,
        public bool $suppressOverrideOnRemoval = false,
        public bool $reinstateDeleted = true,
        public bool $silently = false,
    ) {}
}
