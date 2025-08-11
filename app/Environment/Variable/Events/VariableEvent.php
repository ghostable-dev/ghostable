<?php

namespace App\Environment\Variable\Events;

use App\Environment\Variable\Models\EnvironmentVariable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

abstract class VariableEvent
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public EnvironmentVariable $variable,
    ) {}
}
