<?php

namespace App\Billing\Events;

use App\Billing\Entities\StripePayload;
use App\Billing\Enums\SubscriptionType;
use App\Team\Models\Team;

class SubscriptionEnded extends StripeEvent
{
    public function __construct(
        public Team $team,
        public SubscriptionType $type,
        public ?StripePayload $data = null,
    ) {}
}
