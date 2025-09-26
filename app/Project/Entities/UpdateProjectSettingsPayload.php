<?php

namespace App\Project\Entities;

use App\Project\Enums\DeploymentProvider;
use Spatie\LaravelData\Data;

class UpdateProjectSettingsPayload extends Data
{
    public function __construct(
        public string $name,
        public ?string $description,
        public DeploymentProvider $deploymentProvider,
    ) {}
}
