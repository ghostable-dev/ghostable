<?php

namespace App\Organization\Entities;

use Spatie\LaravelData\Data;

abstract class OrganizationLimits extends Data
{
    public function __construct(
        public readonly ?int $users = null,
        public readonly ?int $projects = null,
        public readonly ?int $environments_per_project = null,
        public readonly ?int $api_operations = null,
    ) {}
}
