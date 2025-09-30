<?php

namespace App\Environment\Deployment\Contracts;

use App\Environment\Deployment\Entities\DeploymentData;
use App\Environment\Models\Environment;

interface DeploymentProviderHandler
{
    public function toData(Environment $environment, bool $encrypted = false): DeploymentData;
}
