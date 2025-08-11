<?php

namespace App\Environment\Events;

use App\Environment\Models\Environment;

class EnvironmentNameChanged extends EnvironmentEvent
{
    public function __construct(
        public Environment $environment, 
        public string $old,
        public string $new
    ) 
    {}
}
