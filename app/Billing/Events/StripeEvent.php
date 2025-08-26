<?php

namespace App\Billing\Events;

use App\Billing\Entities\StripePayload;
use App\Organization\Models\Organization;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

abstract class StripeEvent
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public Organization $organization,
        public ?StripePayload $data = null
    ) {}
}
