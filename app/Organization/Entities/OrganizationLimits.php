<?php

namespace App\Organization\Entities;

use App\Billing\Enums\Plan;
use Spatie\LaravelData\Data;

class OrganizationLimits extends Data
{
    public function __construct(
        public readonly ?int $users = null,
        public readonly ?int $projects = null,
        public readonly ?int $environments_per_project = null,
        public readonly ?int $api_operations = null,
    ) {}
    
    /**
     * Create an OrganizationLimits instance based on the given Plan.
     */
    public static function fromPlan(Plan $plan): self
    {
        return match ($plan) {
            Plan::FREE => new self(users: 2, projects: null, environments_per_project: null, api_operations: 5000),
            Plan::STANDARD => new self(users: 5, projects: null, environments_per_project: null, api_operations: 25000),
            Plan::SCALE => new self(users: 10, projects: null, environments_per_project: null, api_operations: 60000),
            Plan::ENTERPRISE => new self(), // unlimited
        };
    }
    
    /**
     * Create a new OrganizationLimits instance with override values applied.
     *
     * Values in the $overrides array will replace the defaults from the current instance
     * if present. This allows partial customization of plan-based limits.
     * 
     * @return self A new OrganizationLimits instance with merged values.
     */
    public function withOverrides(array $overrides): self
    {
        return new self(
            users: $overrides['users'] ?? $this->users,
            projects: $overrides['projects'] ?? $this->projects,
            environments_per_project: $overrides['environments_per_project'] ?? $this->environments_per_project,
            api_operations: $overrides['api_operations'] ?? $this->api_operations,
        );
    }
}
