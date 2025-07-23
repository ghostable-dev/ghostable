<?php

namespace App\Environment\Events;

use App\Environment\Models\EnvironmentVariable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EnvironmentVariableUpdated
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public EnvironmentVariable $variable,
    ) {}
}
