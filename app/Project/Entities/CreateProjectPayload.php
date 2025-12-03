<?php

namespace App\Project\Entities;

use App\Organization\Models\Organization;
use App\Project\Enums\DeploymentProvider;
use Spatie\LaravelData\Data;

class CreateProjectPayload extends Data
{
    public function __construct(
        public string $name,
        public Organization $organization,
        public DeploymentProvider $deploymentProvider = DeploymentProvider::OTHER,
        public ?string $description = null,
        public bool $withDefaultEnvironments = true,
        public ?ProjectStackData $stack = null
    ) {}
}
