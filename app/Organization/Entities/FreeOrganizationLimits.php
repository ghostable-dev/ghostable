<?php

namespace App\Organization\Entities;

class FreeOrganizationLimits extends OrganizationLimits
{
    public function __construct(
        public readonly ?int $users = 2,
        public readonly ?int $projects = null,
        public readonly ?int $environments_per_project = null,
        public readonly ?int $api_operations = 5000,
    ) {}
}
