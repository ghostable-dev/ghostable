<?php

namespace App\Organization\Entities;

use Spatie\LaravelData\Data;

abstract class OrganizationFeatures extends Data
{
    public function __construct(
        public readonly bool $audits,
        public readonly bool $integrations,
        public readonly bool $advanced_permissions,
        public readonly string $kind,
    ) {}
}
