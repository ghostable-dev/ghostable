<?php

namespace App\Environment\Events;

use App\Environment\Models\Environment;

class EnvironmentBaseChanged extends EnvironmentEvent
{
    public function __construct(
        public Environment $environment,
        public string $oldId,
        public string $newId
    ) {}
}
