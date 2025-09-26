<?php

namespace App\Environment\Validation\Contracts;

use App\Environment\Models\Environment;
use App\Environment\Validation\Entities\EnvironmentValidationPlan;

interface MakesValidationPlan
{
    public function make(Environment $environment): EnvironmentValidationPlan;
}
