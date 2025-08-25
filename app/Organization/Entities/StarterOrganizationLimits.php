<?php

namespace App\Organization\Entities;

class StarterOrganizationLimits extends OrganizationLimits
{
    public function __construct(
        public readonly ?int $users = 5,
        public readonly ?int $projects = null,
        public readonly ?int $environments_per_project = null,
        public readonly ?int $api_operations = 25000,
    ) {}
}
