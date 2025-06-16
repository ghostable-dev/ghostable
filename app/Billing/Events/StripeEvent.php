<?php

namespace App\Billing\Events;

use App\Billing\Entities\StripePayload;
use App\Team\Models\Team;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

abstract class StripeEvent
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;
    
    public function __construct(
        public Team $team,
        public ?StripePayload $data = null
    ) {}
}
